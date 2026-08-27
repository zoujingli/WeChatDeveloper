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
use We\Common\MultipartPart;
use We\Common\Request;
use We\Common\Runtime;
use We\Common\Support\CredentialValidator;
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

    protected function buildRequest(Request $request, EncodedBody $body): RequestInterface
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
        $params = $this->gatewayParameters($request);
        ksort($params);
        $signContent = [];
        foreach ($params as $name => $value) {
            if ($value !== '') {
                $signContent[] = $name . '=' . $value;
            }
        }
        $algorithm = strtoupper($this->config->signType) === 'RSA' ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256;
        $params['sign'] = $this->config->signer->sign(implode('&', $signContent), $algorithm);

        if ($request->method === 'GET') {
            $uri = (new Uri($this->config->endpoint->baseUri))->withQuery($this->queryString($params));
            $encoded = new EncodedBody(Utils::streamFor(''), null, 0);

            return $this->httpRequest($request, $uri, $encoded, ['Accept' => 'application/json, application/xml']);
        }

        $uri = new Uri($this->config->endpoint->baseUri);
        if ($request->bodyType === Request::BODY_MULTIPART) {
            $parts = [];
            foreach ($params as $name => $value) {
                $parts[] = new MultipartPart($name, $value);
            }
            foreach ($request->parts as $part) {
                if (!is_string($part->contents) || $part->filename !== null) {
                    $parts[] = $part;
                }
            }
            $encoded = (new BodyEncoder())->encode(Request::post($request->target)->multipart(...$parts));
        } else {
            $encoded = (new BodyEncoder())->encode(Request::post($request->target)->form($params));
        }

        return $this->httpRequest($request, $uri, $encoded, ['Accept' => 'application/json, application/xml']);
    }

    protected function verifyResponse(Request $request, ResponseInterface $response, ?string $body): void
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
        $decoded = base64_decode($signature, true);
        $key = CredentialValidator::loadPublicKey(
            $this->config->trust->publicKey(self::NAME, $keyId),
            '支付宝 v2 平台信任材料',
            exceptionClass: SignatureException::class,
        );
        $algorithm = strtoupper($this->config->signType) === 'RSA' ? OPENSSL_ALGO_SHA1 : OPENSSL_ALGO_SHA256;
        if ($decoded === false || @openssl_verify($signed, $decoded, $key, $algorithm) !== 1) {
            throw new SignatureException('支付宝 v2 响应验签失败', channel: self::NAME);
        }
    }

    protected function normalizeJson(Request $request, ResponseInterface $response, mixed $value): mixed
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
    protected function normalizeXml(Request $request, ResponseInterface $response, array $value): array
    {
        $node = $this->responseNode($request, $value);
        $data = $value[$node] ?? null;
        if (!is_array($data)) {
            throw new ProtocolException('支付宝 v2 XML 响应缺少节点 `' . $node . '`', channel: self::NAME);
        }

        return $data;
    }

    protected function assertJsonSuccess(Request $request, ResponseInterface $response, mixed $value): void
    {
        $this->assertGatewaySuccess($response, $value);
    }

    /** @param array<string,mixed> $value */
    protected function assertXmlSuccess(Request $request, ResponseInterface $response, array $value): void
    {
        $this->assertGatewaySuccess($response, $value);
    }

    /** @return array<string,string> */
    private function gatewayParameters(Request $request): array
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
        if ($request->identity === Request::IDENTITY_ALIPAY_USER && $request->credentialId !== null) {
            $params['auth_token'] = $this->tokens->token(TokenKind::AlipayUser, $request->credentialId);
        } elseif ($request->identity === Request::IDENTITY_ALIPAY_APP && $request->credentialId !== null) {
            $params['app_auth_token'] = $this->tokens->token(TokenKind::AlipayApp, $request->credentialId);
        } elseif (!in_array($request->identity, [Request::IDENTITY_DEFAULT, Request::IDENTITY_ANONYMOUS], true)) {
            throw new InvalidCallException('支付宝 v2 通道不支持该调用身份', channel: self::NAME);
        }

        if ($request->bodyType === Request::BODY_JSON) {
            try {
                $params['biz_content'] = json_encode($request->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException $exception) {
                throw new ProtocolException('支付宝 `biz_content` JSON 编码失败', 0, $exception, channel: self::NAME);
            }
        } elseif ($request->bodyType === Request::BODY_FORM && is_array($request->body)) {
            foreach ($request->body as [$name, $value]) {
                $this->assertBusinessParameter($name);
                $params[$name] = $value;
            }
        } elseif ($request->bodyType === Request::BODY_MULTIPART) {
            foreach ($request->parts as $part) {
                if (is_string($part->contents) && $part->filename === null) {
                    $this->assertBusinessParameter($part->name);
                    $params[$part->name] = $part->contents;
                }
            }
        } elseif ($request->bodyType !== Request::BODY_EMPTY) {
            throw new InvalidCallException('支付宝 v2 仅支持 JSON、表单、`multipart` 或空请求体', channel: self::NAME);
        }
        foreach ($request->query as [$name, $value]) {
            $this->assertBusinessParameter($name);
            $params[$name] = $value;
        }

        return $params;
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
    private function responseNode(Request $request, array $value): string
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
