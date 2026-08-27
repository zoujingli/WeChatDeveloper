<?php

declare(strict_types=1);

namespace We\Common;

use Psr\Http\Message\StreamInterface;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\ProtocolException;
use We\Common\Exception\StreamException;

/** 已完成平台协议校验的统一响应。 */
final class Response
{
    /** @internal */
    public const FORMAT_JSON = 'json';

    /** @internal */
    public const FORMAT_XML = 'xml';

    /** @internal */
    public const FORMAT_RAW = 'raw';

    /** @internal */
    public const FORMAT_EMPTY = 'empty';

    /** @internal */
    public const FORMAT_STREAM = 'stream';

    /**
     * @param array<string,list<string>> $headers
     * @param null|array<string,mixed> $xml
     * @internal 平台通道负责构造可信响应
     */
    public function __construct(
        private readonly string $format,
        private readonly StreamInterface $stream,
        private readonly mixed $json,
        private readonly ?array $xml,
        private readonly string $channelName,
        private readonly int $statusCode,
        private readonly array $headers,
        private readonly ?string $requestId = null,
        private readonly ?string $keyId = null,
        private readonly ?int $bytesWritten = null,
        private readonly ?string $digestValue = null,
    ) {}

    /** 返回生成该响应的稳定平台通道标识。 */
    public function channel(): string
    {
        return $this->channelName;
    }

    /** 返回平台原始 HTTP 状态码。 */
    public function status(): int
    {
        return $this->statusCode;
    }

    /** @return array<string,list<string>> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** 不区分大小写读取响应头，并用逗号与空格连接多个值。 */
    public function header(string $name): ?string
    {
        foreach ($this->headers as $candidate => $values) {
            if (strcasecmp($candidate, $name) === 0) {
                return implode(', ', $values);
            }
        }

        return null;
    }

    /** 返回平台请求 ID；响应未提供时为 `null`。 */
    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /** 返回响应验签使用的序列号或密钥 ID；未验签时为 `null`。 */
    public function keyId(): ?string
    {
        return $this->keyId;
    }

    /** 返回 JSON 解析值；响应不是 JSON 时抛出 `ProtocolException`。 */
    public function json(): mixed
    {
        if ($this->format !== self::FORMAT_JSON) {
            throw new ProtocolException('当前响应不是 JSON');
        }

        return $this->json;
    }

    /** @return array<string,mixed> */
    public function xml(): array
    {
        if ($this->format !== self::FORMAT_XML || $this->xml === null) {
            throw new ProtocolException('当前响应不是 XML');
        }

        return $this->xml;
    }

    /** 返回响应流；调用方负责关闭原始字节响应使用的临时流。 */
    public function body(): StreamInterface
    {
        return $this->stream;
    }

    /** 可回绕流从开头读取并恢复位置，不可回绕流从当前位置读取。 */
    public function raw(): string
    {
        try {
            $position = $this->stream->isSeekable() ? $this->stream->tell() : null;
            if ($this->stream->isSeekable()) {
                $this->stream->rewind();
            }
            $contents = $this->stream->getContents();
            if ($position !== null) {
                $this->stream->seek($position);
            }

            return $contents;
        } catch (\RuntimeException $exception) {
            throw new StreamException('读取平台响应流失败', 0, $exception, channel: $this->channelName);
        }
    }

    /** 返回下载写入目标流的字节数；非下载响应为 `null`。 */
    public function bytesWritten(): ?int
    {
        return $this->bytesWritten;
    }

    /** 返回下载内容摘要；未要求摘要校验时为 `null`。 */
    public function digest(): ?string
    {
        return $this->digestValue;
    }

    /** 从 JSON 顶级字段创建不携带平台凭证的派生资源。 */
    public function resource(string $urlField, ?string $digestField = null, string $digestAlgorithm = 'sha256'): Resource
    {
        return $this->issueResource($urlField, false, $digestField, $digestAlgorithm);
    }

    /** 从 JSON 顶级字段创建需要来源平台通道重新签名的派生资源。 */
    public function signedResource(string $urlField, ?string $digestField = null, string $digestAlgorithm = 'sha256'): Resource
    {
        return $this->issueResource($urlField, true, $digestField, $digestAlgorithm);
    }

    private function issueResource(string $urlField, bool $signed, ?string $digestField, string $digestAlgorithm): Resource
    {
        $value = $this->json();
        if (!is_array($value) || !is_string($value[$urlField] ?? null)) {
            throw new InvalidCallException('平台响应缺少资源 URL 字段 `' . $urlField . '`', channel: $this->channelName);
        }
        $expectedDigest = null;
        if ($digestField !== null) {
            if (!is_string($value[$digestField] ?? null) || trim($value[$digestField]) === '') {
                throw new InvalidCallException('平台响应缺少资源摘要字段 `' . $digestField . '`', channel: $this->channelName);
            }
            $expectedDigest = $value[$digestField];
        }

        return Resource::issue(
            $this->channelName,
            $value[$urlField],
            $signed,
            $expectedDigest === null ? null : $digestAlgorithm,
            $expectedDigest,
        );
    }
}
