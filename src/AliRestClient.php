<?php

declare(strict_types=1);

namespace We;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Alipay\AliRestConfig;
use We\Alipay\Common\TokenKind;
use We\Alipay\Common\TokenProviderInterface;
use We\Common\AbstractClient;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\SignatureException;
use We\Common\Internal\RequestState;
use We\Common\Resource;
use We\Common\Runtime;
use We\Common\Support\CredentialValidator;
use We\Common\Transport\EncodedBody;
use We\Common\Transport\UriBuilder;

/** 支付宝 REST v3 报文签名与响应验签通道 Client。 */
final class AliRestClient extends AbstractClient
{
    public const NAME = 'alipay.rest';

    private readonly TokenProviderInterface $tokens;

    private function __construct(
        private readonly AliRestConfig $config,
        Runtime $runtime,
    ) {
        $this->tokens = $runtime->tokens();
        parent::__construct($runtime->transport(), $runtime->spooler(), resourcePolicy: $runtime->resourcePolicy());
    }

    /** 使用支付宝 REST v3 配置创建通道 Client。 */
    public static function mk(AliRestConfig $config, ?Runtime $runtime = null): self
    {
        return new self($config, $runtime ?? new Runtime());
    }

    public function channel(): string
    {
        return self::NAME;
    }

    protected function validateRequest(RequestState $request): void
    {
        if ($request->rawMedia) {
            throw new InvalidCallException('`rawMedia()` 仅适用于支付宝 v2 Gateway', channel: self::NAME);
        }
        if ($request->sensitiveKeyId !== null) {
            throw new InvalidCallException('`sensitiveKey()` 仅适用于微信支付', channel: self::NAME);
        }
        if ($request->hasQuery('auth_token')) {
            throw new InvalidCallException('`auth_token` 必须由调用身份解析', channel: self::NAME);
        }
        if (!in_array($request->identity, [
            RequestState::IDENTITY_DEFAULT,
            RequestState::IDENTITY_ANONYMOUS,
            RequestState::IDENTITY_ALIPAY_USER,
            RequestState::IDENTITY_ALIPAY_APP,
        ], true)) {
            throw new InvalidCallException('支付宝 v3 通道不支持该调用身份', channel: self::NAME);
        }
        if ($request->target instanceof Resource) {
            if ($request->identity !== RequestState::IDENTITY_DEFAULT) {
                throw new InvalidCallException('支付宝派生资源只使用来源通道默认身份', channel: self::NAME);
            }
            $this->assertResource($request->target);
            if ($request->query !== []) {
                throw new InvalidCallException('派生资源 URL 不接受额外查询参数', channel: self::NAME);
            }
        }
        if ($request->bodyType === RequestState::BODY_MULTIPART) {
            $this->multipartData($request);
        }
    }

    /** @return array<string,string> */
    protected function resolveCredentials(RequestState $request): array
    {
        if ($request->identity === RequestState::IDENTITY_ALIPAY_USER && $request->credentialId !== null) {
            return ['auth_token' => $this->tokens->token(TokenKind::AlipayUser, $request->credentialId)];
        }
        if ($request->identity === RequestState::IDENTITY_ALIPAY_APP && $request->credentialId !== null) {
            return ['app_auth_token' => $this->tokens->token(TokenKind::AlipayApp, $request->credentialId)];
        }

        return [];
    }

    /** @param array<string,string> $credentials */
    protected function buildRequest(RequestState $request, EncodedBody $body, array $credentials): RequestInterface
    {
        $query = $request->query;
        $headers = ['Accept' => 'application/json'];
        if (isset($credentials['auth_token'])) {
            $query[] = ['auth_token', $credentials['auth_token']];
        }
        if (isset($credentials['app_auth_token'])) {
            $headers['alipay-app-auth-token'] = $credentials['app_auth_token'];
        }

        if (is_string($request->target)) {
            $uri = UriBuilder::build($this->config->endpoint->baseUri, $request->target, $query);
            $signed = true;
        } else {
            $uri = new Uri($request->target->url);
            $signed = $request->target->signed;
        }

        [$contents, $body] = $this->signingBody($request, $body);
        if ($signed) {
            $nonce = $this->uuid();
            $timestamp = (string)(int)floor(microtime(true) * 1000);
            $auth = 'app_id=' . $this->config->appid
                . ($this->config->appCertificateSerial === '' ? '' : ',app_cert_sn=' . $this->config->appCertificateSerial)
                . ',nonce=' . $nonce
                . ',timestamp=' . $timestamp;
            $requestUri = $uri->getPath() . ($uri->getQuery() === '' ? '' : '?' . $uri->getQuery());
            $appAuth = $headers['alipay-app-auth-token'] ?? '';
            $message = $auth . "\n"
                . $request->method . "\n"
                . $requestUri . "\n"
                . $contents . "\n"
                . ($appAuth === '' ? '' : $appAuth . "\n");
            $headers['Authorization'] = 'ALIPAY-SHA256withRSA ' . $auth . ',sign=' . $this->config->signer->sign($message);
        }

        return $this->httpRequest($request, $uri, $body, $headers);
    }

    protected function verifyResponse(RequestState $request, ResponseInterface $response, ?string $body): void
    {
        if ($request->target instanceof Resource) {
            return;
        }
        $signature = $response->getHeaderLine('alipay-signature');
        $keyId = $response->getHeaderLine('alipay-sn');
        $timestamp = $response->getHeaderLine('alipay-timestamp');
        $nonce = $response->getHeaderLine('alipay-nonce');
        if ($signature === '' || $timestamp === '' || $nonce === '' || $body === null) {
            throw new SignatureException('支付宝 v3 响应缺少完整验签响应头', channel: self::NAME);
        }
        if ($keyId === '') {
            $keyId = $this->config->defaultTrustKeyId;
        }
        $decoded = base64_decode($signature, true);
        $publicKey = CredentialValidator::loadPublicKey(
            $this->config->trust->publicKey(self::NAME, $keyId),
            '支付宝 v3 平台信任材料',
            exceptionClass: SignatureException::class,
        );
        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";
        if ($decoded === false || @openssl_verify($message, $decoded, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new SignatureException('支付宝 v3 响应验签失败', channel: self::NAME);
        }
    }

    protected function assertJsonSuccess(RequestState $request, ResponseInterface $response, mixed $value): void
    {
        if ($response->getStatusCode() >= 400) {
            $data = is_array($value) ? $value : [];
            $code = $data['code'] ?? $data['error_code'] ?? $response->getStatusCode();
            throw new PlatformException(
                (string)($data['message'] ?? $data['msg'] ?? '支付宝 v3 API 调用失败'),
                context: [
                    'status' => $response->getStatusCode(),
                    'code' => is_scalar($code) ? (string)$code : null,
                    'message' => is_scalar($data['message'] ?? null) ? (string)$data['message'] : null,
                    'msg' => is_scalar($data['msg'] ?? null) ? (string)$data['msg'] : null,
                ],
                channel: self::NAME,
                platformCode: is_scalar($code) ? (string)$code : $response->getStatusCode(),
            );
        }
    }

    protected function responseKeyId(ResponseInterface $response): ?string
    {
        $keyId = $response->getHeaderLine('alipay-sn');

        return $keyId === '' ? null : $keyId;
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    /** @return array{0:string,1:EncodedBody} */
    private function signingBody(RequestState $request, EncodedBody $body): array
    {
        if ($request->bodyType !== RequestState::BODY_MULTIPART) {
            return $this->signableBody($request, $body);
        }

        return [$this->multipartData($request), $body];
    }

    private function multipartData(RequestState $request): string
    {
        $data = '';
        $found = false;
        foreach ($request->parts as $part) {
            if ($part->filename !== null || !is_string($part->contents)) {
                continue;
            }
            if ($part->name !== 'data') {
                throw new InvalidCallException('支付宝 v3 `multipart` 普通字段只支持 `data`', channel: self::NAME);
            }
            if ($found) {
                throw new InvalidCallException('支付宝 v3 `multipart` 只能包含一个 `data` 字段', channel: self::NAME);
            }
            $data = $part->contents;
            $found = true;
        }

        return $data;
    }

    private function assertResource(Resource $resource): void
    {
        if ($resource->channel !== self::NAME) {
            throw new InvalidCallException('派生资源不属于支付宝 REST 通道', channel: self::NAME);
        }
        $this->resourcePolicy->assertAllowed(self::NAME, $resource);
    }
}
