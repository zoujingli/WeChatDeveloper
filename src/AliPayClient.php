<?php

declare(strict_types=1);

namespace We;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Alipay\AliPayConfig;
use We\Alipay\Common\TokenKind;
use We\Alipay\Common\TokenProviderInterface;
use We\Common\AbstractClient;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Exception\SignatureException;
use We\Common\Internal\ProviderValue;
use We\Common\Internal\RequestState;
use We\Common\Internal\RsaVerifier;
use We\Common\MultipartPart;
use We\Common\Runtime;
use We\Common\Support\XmlCodec;
use We\Common\Transport\BodyEncoder;
use We\Common\Transport\EncodedBody;

/** 支付宝支付 v2 AOP Gateway 通道 Client。 */
final class AliPayClient extends AbstractClient
{
    public const NAME = 'alipay.gateway';

    private readonly TokenProviderInterface $tokens;

    private function __construct(
        private readonly AliPayConfig $config,
        Runtime $runtime,
    ) {
        $this->tokens = $runtime->tokens();
        parent::__construct($runtime->transport(), $runtime->spooler(), resourcePolicy: $runtime->resourcePolicy());
    }

    /** 使用支付宝支付 v2 AOP Gateway 配置创建通道 Client。 */
    public static function mk(AliPayConfig $config, ?Runtime $runtime = null): self
    {
        return new self($config, $runtime ?? new Runtime());
    }

    public function channel(): string
    {
        return self::NAME;
    }

    protected function validateRequest(RequestState $request): void
    {
        if ($request->sensitiveKeyId !== null) {
            throw new InvalidCallException('`sensitiveKey()` 仅适用于微信支付', channel: self::NAME);
        }
        if (!is_string($request->target) || preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)+$/D', $request->target) !== 1) {
            throw new InvalidCallException('支付宝 v2 Gateway 方法无效', channel: self::NAME);
        }
        if (!in_array($request->method, ['GET', 'POST'], true)) {
            throw new InvalidCallException('支付宝 v2 Gateway 只支持 GET 或 POST', channel: self::NAME);
        }
        if ($request->method === 'GET' && $request->bodyType === RequestState::BODY_MULTIPART) {
            throw new InvalidCallException('支付宝 v2 Gateway GET 不支持 `multipart` 请求体', channel: self::NAME);
        }
        if (!in_array($request->identity, [
            RequestState::IDENTITY_DEFAULT,
            RequestState::IDENTITY_ANONYMOUS,
            RequestState::IDENTITY_ALIPAY_USER,
            RequestState::IDENTITY_ALIPAY_APP,
        ], true)) {
            throw new InvalidCallException('支付宝 v2 通道不支持该调用身份', channel: self::NAME);
        }
        $this->assertBusinessPayload($request);
    }

    /** @return array<string,string> */
    protected function resolveCredentials(RequestState $request): array
    {
        if ($request->identity === RequestState::IDENTITY_ALIPAY_USER && $request->credentialId !== null) {
            return ['auth_token' => ProviderValue::token(
                $this->tokens->token(TokenKind::AlipayUser, $request->credentialId),
                self::NAME,
            )];
        }
        if ($request->identity === RequestState::IDENTITY_ALIPAY_APP && $request->credentialId !== null) {
            return ['app_auth_token' => ProviderValue::token(
                $this->tokens->token(TokenKind::AlipayApp, $request->credentialId),
                self::NAME,
            )];
        }

        return [];
    }

    /** @param array<string,string> $credentials */
    protected function buildRequest(RequestState $request, EncodedBody $body, array $credentials): RequestInterface
    {
        $params = $this->gatewayParameters($request, $body, $credentials);
        ksort($params);
        $signContent = [];
        foreach ($params as $name => $value) {
            if ($value !== '') {
                $signContent[] = $name . '=' . $value;
            }
        }
        $algorithm = strtoupper($this->config->signType) === 'RSA' ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256;
        $params['sign'] = ProviderValue::signature(
            $this->config->signer->sign(implode('&', $signContent), $algorithm),
            self::NAME,
        );

        if ($request->method === 'GET') {
            $uri = (new Uri($this->config->endpoint->baseUri))->withQuery($this->queryString($params));
            $encoded = new EncodedBody(Utils::streamFor(''), null, 0);

            return $this->httpRequest($request, $uri, $encoded, ['Accept' => 'application/json, application/xml']);
        }

        $uri = new Uri($this->config->endpoint->baseUri);
        if ($request->bodyType === RequestState::BODY_MULTIPART) {
            $parts = [];
            foreach ($params as $name => $value) {
                $parts[] = new MultipartPart($name, $value);
            }
            foreach ($request->parts as $part) {
                if (!is_string($part->contents) || $part->filename !== null) {
                    $parts[] = $part;
                }
            }
            $encoded = (new BodyEncoder())->encode(new RequestState(
                'POST',
                $request->target,
                bodyType: RequestState::BODY_MULTIPART,
                parts: $parts,
            ));
        } else {
            $fields = [];
            foreach ($params as $name => $value) {
                $fields[] = [$name, $value];
            }
            $encoded = (new BodyEncoder())->encode(new RequestState(
                'POST',
                $request->target,
                bodyType: RequestState::BODY_FORM,
                body: $fields,
            ));
        }

        return $this->httpRequest($request, $uri, $encoded, ['Accept' => 'application/json, application/xml']);
    }

    protected function verifyResponse(RequestState $request, ResponseInterface $response, ?string $body): void
    {
        if ($request->rawMedia) {
            return;
        }
        if (!is_string($request->target) || $body === null) {
            throw new SignatureException('支付宝 v2 响应缺少验签上下文', channel: self::NAME);
        }
        if (strtoupper($this->config->format) === 'XML') {
            $parsed = XmlCodec::decode($body);
            $signature = is_string($parsed['sign'] ?? null) ? $parsed['sign'] : '';
            $keyId = is_string($parsed['alipay_cert_sn'] ?? null) ? $parsed['alipay_cert_sn'] : $this->config->defaultTrustKeyId;
            $node = $this->responseNode($request, $parsed);
            [$signed, $inner] = $this->extractXmlResponse($body, $node);
            $verifiedNode = XmlCodec::decode('<response>' . $inner . '</response>');
            if (!is_array($parsed[$node] ?? null) || $verifiedNode !== $parsed[$node]) {
                throw new SignatureException('支付宝 v2 XML 验签节点与解析结果不一致', channel: self::NAME);
            }
        } else {
            try {
                $parsed = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ProtocolException('支付宝 v2 响应不是有效 JSON', 0, $exception, channel: self::NAME);
            }
            if (!is_array($parsed)) {
                throw new ProtocolException('支付宝 v2 响应结构无效', channel: self::NAME);
            }
            $signature = is_string($parsed['sign'] ?? null) ? $parsed['sign'] : '';
            $keyId = is_string($parsed['alipay_cert_sn'] ?? null) ? $parsed['alipay_cert_sn'] : $this->config->defaultTrustKeyId;
            $node = array_key_exists('error_response', $parsed)
                ? 'error_response'
                : str_replace('.', '_', $request->target) . '_response';
            $signed = $this->extractJsonValue($body, $node);
            try {
                $verifiedNode = json_decode($signed, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ProtocolException('支付宝 v2 验签节点不是有效 JSON', 0, $exception, channel: self::NAME);
            }
            if (!is_array($verifiedNode) || !is_array($parsed[$node] ?? null) || $verifiedNode !== $parsed[$node]) {
                throw new SignatureException('支付宝 v2 验签节点与解析结果不一致', channel: self::NAME);
            }
        }
        if ($signature === '') {
            throw new SignatureException('支付宝 v2 响应缺少签名', channel: self::NAME);
        }
        $algorithm = strtoupper($this->config->signType) === 'RSA' ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256;
        RsaVerifier::verify(
            $this->config->trust,
            self::NAME,
            $keyId,
            $signed,
            $signature,
            '支付宝 v2 平台信任材料',
            '支付宝 v2 响应验签失败',
            $algorithm,
        );
    }

    protected function normalizeJson(RequestState $request, ResponseInterface $response, mixed $value): mixed
    {
        if (!is_array($value) || !is_string($request->target)) {
            throw new ProtocolException('支付宝 v2 JSON 响应结构无效', channel: self::NAME);
        }
        $node = array_key_exists('error_response', $value)
            ? 'error_response'
            : str_replace('.', '_', $request->target) . '_response';
        $data = $value[$node] ?? null;
        if (!is_array($data)) {
            throw new ProtocolException('支付宝 v2 响应缺少节点 `' . $node . '`', channel: self::NAME);
        }

        return $data;
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    protected function normalizeXml(RequestState $request, ResponseInterface $response, array $value): array
    {
        $node = $this->responseNode($request, $value);
        $data = $value[$node] ?? null;
        if (!is_array($data)) {
            throw new ProtocolException('支付宝 v2 XML 响应缺少节点 `' . $node . '`', channel: self::NAME);
        }

        return $data;
    }

    protected function assertJsonSuccess(RequestState $request, ResponseInterface $response, mixed $value): void
    {
        $this->assertGatewaySuccess($response, $value);
    }

    /** @param array<string,mixed> $value */
    protected function assertXmlSuccess(RequestState $request, ResponseInterface $response, array $value): void
    {
        $this->assertGatewaySuccess($response, $value);
    }

    /** @return array<string,string> */
    private function gatewayParameters(RequestState $request, EncodedBody $body, array $credentials): array
    {
        $params = [
            'app_id' => $this->config->appid,
            'method' => is_string($request->target) ? $request->target : '',
            'format' => strtoupper($this->config->format),
            'charset' => $this->config->charset,
            'sign_type' => strtoupper($this->config->signType),
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->config->version,
        ];
        if ($this->config->appCertificateSerial !== '') {
            $params['app_cert_sn'] = $this->config->appCertificateSerial;
        }
        if ($this->config->alipayRootCertificateSerial !== '') {
            $params['alipay_root_cert_sn'] = $this->config->alipayRootCertificateSerial;
        }
        foreach ($credentials as $name => $value) {
            $params[$name] = $value;
        }

        if ($request->bodyType === RequestState::BODY_JSON) {
            $params['biz_content'] = $this->encodedBodyContents($body);
        } elseif ($request->bodyType === RequestState::BODY_FORM && is_array($request->body)) {
            foreach ($request->body as [$name, $value]) {
                $params[$name] = $value;
            }
        } elseif ($request->bodyType === RequestState::BODY_MULTIPART) {
            foreach ($request->parts as $part) {
                if (is_string($part->contents) && $part->filename === null) {
                    $params[$part->name] = $part->contents;
                }
            }
        }
        foreach ($request->query as [$name, $value]) {
            $params[$name] = $value;
        }

        return $params;
    }

    private function encodedBodyContents(EncodedBody $body): string
    {
        try {
            $position = $body->stream->tell();
            $contents = $body->stream->getContents();
            $body->stream->seek($position);
        } catch (\RuntimeException $exception) {
            throw new ProtocolException('读取支付宝 `biz_content` 编码结果失败', 0, $exception, channel: self::NAME);
        }

        return $contents;
    }

    private function assertBusinessPayload(RequestState $request): void
    {
        if ($request->bodyType === RequestState::BODY_FORM && is_array($request->body)) {
            foreach ($request->body as [$name]) {
                $this->assertBusinessParameter($name);
            }
        } elseif ($request->bodyType === RequestState::BODY_MULTIPART) {
            foreach ($request->parts as $part) {
                if (is_string($part->contents) && $part->filename === null) {
                    $this->assertBusinessParameter($part->name);
                }
            }
        } elseif (!in_array($request->bodyType, [RequestState::BODY_EMPTY, RequestState::BODY_JSON], true)) {
            throw new InvalidCallException('支付宝 v2 仅支持 JSON、表单、`multipart` 或空请求体', channel: self::NAME);
        }
        foreach ($request->query as [$name]) {
            $this->assertBusinessParameter($name);
        }
    }

    private function assertBusinessParameter(string $name): void
    {
        if (in_array($name, [
            'app_id',
            'method',
            'format',
            'charset',
            'sign_type',
            'sign',
            'timestamp',
            'version',
            'app_cert_sn',
            'alipay_root_cert_sn',
            'auth_token',
            'app_auth_token',
        ], true)) {
            throw new InvalidCallException('支付宝 AOP 公共参数 `' . $name . '` 必须由通道生成', channel: self::NAME);
        }
    }

    /** @param array<string,string> $params */
    private function queryString(array $params): string
    {
        $pairs = [];
        foreach ($params as $name => $value) {
            $pairs[] = rawurlencode($name) . '=' . rawurlencode($value);
        }

        return implode('&', $pairs);
    }

    private function extractJsonValue(string $json, string $key): string
    {
        if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*/', $json, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw new ProtocolException('支付宝 v2 响应缺少签名节点', channel: self::NAME);
        }
        $start = (int)$match[0][1] + strlen((string)$match[0][0]);
        $depth = 0;
        $inString = false;
        $escaped = false;
        for ($index = $start, $length = strlen($json); $index < $length; ++$index) {
            $char = $json[$index];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($char === '"') {
                $inString = true;
            } elseif ($char === '{' || $char === '[') {
                ++$depth;
            } elseif ($char === '}' || $char === ']') {
                --$depth;
                if ($depth === 0) {
                    return substr($json, $start, $index - $start + 1);
                }
            }
        }

        throw new ProtocolException('支付宝 v2 响应签名节点不完整', channel: self::NAME);
    }

    /** @param array<string,mixed> $value */
    private function responseNode(RequestState $request, array $value): string
    {
        if (array_key_exists('error_response', $value)) {
            return 'error_response';
        }
        if (!is_string($request->target)) {
            throw new ProtocolException('支付宝 v2 响应缺少 Gateway 方法上下文', channel: self::NAME);
        }

        return str_replace('.', '_', $request->target) . '_response';
    }

    /** @return array{0:string,1:string} */
    private function extractXmlResponse(string $xml, string $node): array
    {
        $open = '<' . $node . '>';
        $close = '</' . $node . '>';
        $start = strpos($xml, $open);
        $end = strpos($xml, $close);
        $sign = strrpos($xml, '<sign>');
        if (
            $start === false
            || $end === false
            || $sign === false
            || substr_count($xml, $open) !== 1
            || substr_count($xml, $close) !== 1
        ) {
            throw new ProtocolException('支付宝 v2 XML 响应缺少签名节点', channel: self::NAME);
        }
        $content = $start + strlen($open);
        $closeEnd = $end + strlen($close);
        if ($end < $content || $sign < $closeEnd) {
            throw new ProtocolException('支付宝 v2 XML 响应签名节点顺序无效', channel: self::NAME);
        }

        return [
            substr($xml, $content, $sign - $content),
            substr($xml, $content, $end - $content),
        ];
    }

    private function assertGatewaySuccess(ResponseInterface $response, mixed $value): void
    {
        $data = is_array($value) ? $value : [];
        $code = $data['code'] ?? null;
        if ($response->getStatusCode() >= 400 || !is_scalar($code) || (string)$code !== '10000') {
            throw new PlatformException(
                (string)($data['sub_msg'] ?? $data['msg'] ?? '支付宝 v2 API 调用失败'),
                context: [
                    'status' => $response->getStatusCode(),
                    'code' => is_scalar($data['code'] ?? null) ? (string)$data['code'] : null,
                    'msg' => is_scalar($data['msg'] ?? null) ? (string)$data['msg'] : null,
                    'sub_code' => is_scalar($data['sub_code'] ?? null) ? (string)$data['sub_code'] : null,
                    'sub_msg' => is_scalar($data['sub_msg'] ?? null) ? (string)$data['sub_msg'] : null,
                ],
                channel: self::NAME,
                platformCode: is_scalar($code) ? (string)$code : $response->getStatusCode(),
            );
        }
    }
}
