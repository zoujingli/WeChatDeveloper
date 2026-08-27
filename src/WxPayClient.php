<?php

declare(strict_types=1);

namespace We;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
use We\Wechat\WxPayConfig;

/** 微信支付 APIv3 请求签名、响应验签与派生资源通道 Client。 */
final class WxPayClient extends AbstractClient
{
    public const NAME = 'wechat.payment';

    private function __construct(
        private readonly WxPayConfig $config,
        Runtime $runtime,
    ) {
        parent::__construct($runtime->transport(), $runtime->spooler(), resourcePolicy: $runtime->resourcePolicy());
    }

    /** 使用微信支付 APIv3 配置创建通道 Client。 */
    public static function mk(WxPayConfig $config, ?Runtime $runtime = null): self
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
        if ($request->identity !== RequestState::IDENTITY_DEFAULT) {
            throw new InvalidCallException('微信支付通道只支持商户默认身份', channel: self::NAME);
        }
        if ($request->target instanceof Resource) {
            $this->assertResource($request->target);
            if ($request->query !== []) {
                throw new InvalidCallException('派生资源 URL 不接受额外查询参数', channel: self::NAME);
            }
        }
    }

    /** @param array<string,string> $credentials */
    protected function buildRequest(RequestState $request, EncodedBody $body, array $credentials): RequestInterface
    {
        if (is_string($request->target)) {
            $uri = UriBuilder::build($this->config->endpoint->baseUri, $request->target, $request->query);
        } else {
            $uri = new Uri($request->target->url);
        }

        [$contents, $body] = $this->signableBody($request, $body);
        $headers = ['Accept' => 'application/json'];
        if ($request->sensitiveKeyId !== null) {
            $headers['Wechatpay-Serial'] = $request->sensitiveKeyId;
        }
        $requiresSignature = is_string($request->target) || $request->target->signed;
        if ($requiresSignature) {
            $timestamp = (string)time();
            $nonce = bin2hex(random_bytes(16));
            $path = $uri->getPath() . ($uri->getQuery() === '' ? '' : '?' . $uri->getQuery());
            $message = implode("\n", [$request->method, $path, $timestamp, $nonce, $contents, '']);
            $signature = $this->config->merchantSigner->sign($message);
            $headers['Authorization'] = sprintf(
                'WECHATPAY2-SHA256-RSA2048 mchid="%s",nonce_str="%s",timestamp="%s",serial_no="%s",signature="%s"',
                $this->config->mchId,
                $nonce,
                $timestamp,
                $this->config->merchantSigner->keyId(),
                $signature,
            );
        }

        return $this->httpRequest($request, $uri, $body, $headers);
    }

    protected function verifyResponse(RequestState $request, ResponseInterface $response, ?string $body): void
    {
        if ($request->target instanceof Resource) {
            return;
        }
        $timestamp = $response->getHeaderLine('Wechatpay-Timestamp');
        $nonce = $response->getHeaderLine('Wechatpay-Nonce');
        $signature = $response->getHeaderLine('Wechatpay-Signature');
        $serial = $response->getHeaderLine('Wechatpay-Serial');
        if ($timestamp === '' || $nonce === '' || $signature === '' || $serial === '' || $body === null) {
            throw new SignatureException('微信支付响应缺少完整验签材料', channel: self::NAME);
        }
        $publicKey = $this->config->platformTrust->publicKey(self::NAME, $serial);
        $decoded = base64_decode($signature, true);
        $key = CredentialValidator::loadPublicKey(
            $publicKey,
            '微信支付平台信任材料',
            exceptionClass: SignatureException::class,
        );
        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";
        if ($decoded === false || @openssl_verify($message, $decoded, $key, OPENSSL_ALGO_SHA256) !== 1) {
            throw new SignatureException('微信支付响应验签失败', channel: self::NAME);
        }
    }

    protected function assertJsonSuccess(RequestState $request, ResponseInterface $response, mixed $value): void
    {
        if ($response->getStatusCode() >= 400) {
            $data = is_array($value) ? $value : [];
            throw new PlatformException(
                (string)($data['message'] ?? '微信支付 API 调用失败'),
                context: [
                    'status' => $response->getStatusCode(),
                    'code' => is_scalar($data['code'] ?? null) ? (string)$data['code'] : null,
                    'message' => is_scalar($data['message'] ?? null) ? (string)$data['message'] : null,
                ],
                channel: self::NAME,
                platformCode: is_scalar($data['code'] ?? null) ? (string)$data['code'] : $response->getStatusCode(),
            );
        }
    }

    protected function responseKeyId(ResponseInterface $response): ?string
    {
        $serial = $response->getHeaderLine('Wechatpay-Serial');

        return $serial === '' ? null : $serial;
    }

    private function assertResource(Resource $resource): void
    {
        if ($resource->channel !== self::NAME) {
            throw new InvalidCallException('派生资源不属于微信支付通道', channel: self::NAME);
        }
        $this->resourcePolicy->assertAllowed(self::NAME, $resource);
    }
}
