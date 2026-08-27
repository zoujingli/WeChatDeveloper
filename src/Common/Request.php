<?php

declare(strict_types=1);

namespace We\Common;

use Psr\Http\Message\StreamInterface;
use We\Common\Exception\InvalidCallException;
use We\Common\Internal\RequestState;

/**
 * 不可变的出站 API 请求。
 *
 * 常见调用只需要 HTTP 方法、目标和一种请求体；身份、超时与流策略按需设置。
 */
final class Request
{
    private function __construct(private readonly RequestState $state) {}

    /** 使用 HTTP 方法和相对目标或可信派生资源创建请求。 */
    public static function create(string $method, Resource|string $target): self
    {
        $method = strtoupper(trim($method));
        if (preg_match("/^[!#$%&'*+.^_`|~0-9A-Z-]+$/D", $method) !== 1) {
            throw new InvalidCallException('HTTP 方法无效');
        }
        if (is_string($target)) {
            $target = self::relativeTarget($target);
        }

        return new self(new RequestState($method, $target));
    }

    public static function get(Resource|string $target): self
    {
        return self::create('GET', $target);
    }

    public static function post(Resource|string $target): self
    {
        return self::create('POST', $target);
    }

    public static function put(Resource|string $target): self
    {
        return self::create('PUT', $target);
    }

    public static function patch(Resource|string $target): self
    {
        return self::create('PATCH', $target);
    }

    public static function delete(Resource|string $target): self
    {
        return self::create('DELETE', $target);
    }

    /** @param array<string, null|bool|float|int|list<null|bool|float|int|string>|string>|list<array{0:string,1:null|bool|float|int|string}> $query */
    public function query(array $query): self
    {
        return $this->copy(query: self::parameterPairs($query));
    }

    /** @param array<string,list<string>|string> $headers */
    public function headers(array $headers): self
    {
        $pairs = [];
        foreach ($headers as $name => $values) {
            foreach (is_array($values) ? $values : [$values] as $value) {
                $name = (string)$name;
                if (!is_string($value)
                    || preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $name) !== 1
                    || preg_match('/[\r\n]/', $value) === 1
                ) {
                    throw new InvalidCallException('HTTP 请求头无效');
                }
                $pairs[] = [$name, $value];
            }
        }

        return $this->copy(headers: $pairs);
    }

    /** 使用调用时只编码一次的 JSON 请求体。 */
    public function json(mixed $value): self
    {
        return $this->copy(bodyType: RequestState::BODY_JSON, body: $value);
    }

    /** @param array<string, null|bool|float|int|list<null|bool|float|int|string>|string>|list<array{0:string,1:null|bool|float|int|string}> $fields */
    public function form(array $fields): self
    {
        return $this->copy(bodyType: RequestState::BODY_FORM, body: self::parameterPairs($fields));
    }

    /**
     * 使用已编码字符串或从当前位置读取的流作为请求体。
     *
     * `knownLength` 必须是流当前位置之后的字节数；流仍由调用方持有。
     */
    public function raw(StreamInterface|string $body, string $mediaType = 'application/octet-stream', ?int $knownLength = null): self
    {
        self::assertMediaType($mediaType);
        if ($body instanceof StreamInterface && !$body->isReadable()) {
            throw new InvalidCallException('请求体流不可读');
        }
        if ($knownLength !== null && $knownLength < 0) {
            throw new InvalidCallException('请求体长度不能小于 0');
        }

        return $this->copy(
            bodyType: is_string($body) ? RequestState::BODY_RAW : RequestState::BODY_STREAM,
            body: $body,
            mediaType: $mediaType,
            bodyLength: is_string($body) ? strlen($body) : $knownLength,
        );
    }

    /** 使用至少一个普通字段或文件部件创建 `multipart/form-data` 请求体。 */
    public function multipart(MultipartPart ...$parts): self
    {
        if ($parts === []) {
            throw new InvalidCallException('`multipart` 请求体至少需要一个部件');
        }

        return $this->copy(bodyType: RequestState::BODY_MULTIPART, parts: $parts);
    }

    /** 不注入通道默认 Token；平台级签名和应用身份仍按通道协议生成。 */
    public function anonymous(): self
    {
        return $this->copy(identity: RequestState::IDENTITY_ANONYMOUS, credentialId: null);
    }

    /** 使用 `authorizer_appid` 引用微信开放平台授权方身份。 */
    public function asWechatAuthorizer(string $appid): self
    {
        return $this->withCredential(RequestState::IDENTITY_WECHAT_AUTHORIZER, $appid);
    }

    /** 使用凭证 ID 引用支付宝用户 `auth_token`。 */
    public function asAlipayUser(string $credentialId): self
    {
        return $this->withCredential(RequestState::IDENTITY_ALIPAY_USER, $credentialId);
    }

    /** 使用凭证 ID 引用支付宝代调用应用 Token。 */
    public function asAlipayApp(string $credentialId): self
    {
        return $this->withCredential(RequestState::IDENTITY_ALIPAY_APP, $credentialId);
    }

    /** 声明支付宝 v2 AOP Gateway 官方不签名的媒体响应。 */
    public function rawMedia(): self
    {
        return $this->copy(rawMedia: true);
    }

    /**
     * 在协议校验和摘要校验通过后把响应复制到目标流。
     *
     * 目标流仍由调用方持有；目标流写入失败时可能保留已复制的部分数据。
     */
    public function downloadTo(
        StreamInterface $destination,
        ?string $digestAlgorithm = null,
        ?string $expectedDigest = null,
    ): self {
        self::assertDigest($digestAlgorithm, $expectedDigest);
        if (!$destination->isWritable()) {
            throw new InvalidCallException('下载目标流不可写');
        }

        return $this->copy(
            destination: $destination,
            digestAlgorithm: $digestAlgorithm,
            expectedDigest: $expectedDigest,
        );
    }

    /** 声明微信支付敏感字段使用的平台证书序列号或密钥 ID。 */
    public function sensitiveKey(string $keyId): self
    {
        $keyId = trim($keyId);
        if ($keyId === '') {
            throw new InvalidCallException('敏感字段密钥 ID 不能为空');
        }

        return $this->copy(sensitiveKeyId: $keyId);
    }

    /** 设置大于 0 的单次 HTTP 调用超时毫秒数。 */
    public function timeout(int $milliseconds): self
    {
        if ($milliseconds <= 0) {
            throw new InvalidCallException('超时时间必须大于 0');
        }

        return $this->copy(timeoutMilliseconds: $milliseconds);
    }

    /** 设置响应以及需要签名的不可回绕请求流的最大暂存字节数。 */
    public function maxResponseBytes(int $bytes): self
    {
        if ($bytes <= 0) {
            throw new InvalidCallException('响应字节上限必须大于 0');
        }

        return $this->copy(maxResponseBytes: $bytes);
    }

    /** @internal 通道管线使用；调用方不得依赖内部状态结构。 */
    public function internalState(): RequestState
    {
        return $this->state;
    }

    private function withCredential(string $identity, string $credentialId): self
    {
        $credentialId = trim($credentialId);
        if ($credentialId === '') {
            throw new InvalidCallException('调用身份凭证引用不能为空');
        }

        return $this->copy(identity: $identity, credentialId: $credentialId);
    }

    /**
     * @param array<string, null|bool|float|int|list<null|bool|float|int|string>|string>|list<array{0:string,1:null|bool|float|int|string}> $values
     * @return list<array{0:string,1:string}>
     */
    private static function parameterPairs(array $values): array
    {
        $pairs = [];
        if (array_is_list($values)) {
            foreach ($values as $pair) {
                if (!is_array($pair) || count($pair) !== 2 || !is_string($pair[0])) {
                    throw new InvalidCallException('参数对必须是 `[name, value]`');
                }
                $pairs[] = [self::parameterName($pair[0]), self::scalar($pair[1])];
            }

            return $pairs;
        }
        foreach ($values as $name => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                $pairs[] = [self::parameterName((string)$name), self::scalar($item)];
            }
        }

        return $pairs;
    }

    private static function parameterName(string $name): string
    {
        if (trim($name) === '') {
            throw new InvalidCallException('参数名称不能为空');
        }

        return $name;
    }

    private static function scalar(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            is_int($value), is_float($value), is_string($value) => (string)$value,
            default => throw new InvalidCallException('参数值必须是标量或 `null`'),
        };
    }

    private static function relativeTarget(string $target): string
    {
        $target = trim($target);
        if (
            $target === ''
            || str_starts_with($target, '//')
            || preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) === 1
            || preg_match('/[?#\\\\\x00-\x1F\x7F]/', $target) === 1
        ) {
            throw new InvalidCallException('API 目标必须是没有查询参数和片段的相对路径或 Gateway 方法');
        }

        return ltrim($target, '/');
    }

    private static function assertMediaType(string $mediaType): void
    {
        if (trim($mediaType) === '' || preg_match('/[\r\n]/', $mediaType) === 1) {
            throw new InvalidCallException('请求体媒体类型无效');
        }
    }

    private static function assertDigest(?string $algorithm, ?string $expected): void
    {
        if (($algorithm === null) !== ($expected === null)) {
            throw new InvalidCallException('摘要算法与摘要值必须同时提供');
        }
        if ($algorithm !== null && !in_array(strtolower($algorithm), hash_algos(), true)) {
            throw new InvalidCallException('摘要算法不受支持');
        }
    }

    /**
     * @param null|list<array{0:string,1:string}> $query
     * @param null|list<array{0:string,1:string}> $headers
     * @param null|list<MultipartPart> $parts
     */
    private function copy(
        ?array $query = null,
        ?array $headers = null,
        ?string $bodyType = null,
        mixed $body = null,
        ?string $mediaType = null,
        ?int $bodyLength = null,
        ?array $parts = null,
        ?string $identity = null,
        ?string $credentialId = null,
        ?bool $rawMedia = null,
        ?StreamInterface $destination = null,
        ?string $digestAlgorithm = null,
        ?string $expectedDigest = null,
        ?string $sensitiveKeyId = null,
        ?int $timeoutMilliseconds = null,
        ?int $maxResponseBytes = null,
    ): self {
        return new self($this->state->with(
            query: $query,
            headers: $headers,
            bodyType: $bodyType,
            body: $body,
            mediaType: $mediaType,
            bodyLength: $bodyLength,
            parts: $parts,
            identity: $identity,
            credentialId: $credentialId,
            rawMedia: $rawMedia,
            destination: $destination,
            digestAlgorithm: $digestAlgorithm,
            expectedDigest: $expectedDigest,
            sensitiveKeyId: $sensitiveKeyId,
            timeoutMilliseconds: $timeoutMilliseconds,
            maxResponseBytes: $maxResponseBytes,
        ));
    }
}
