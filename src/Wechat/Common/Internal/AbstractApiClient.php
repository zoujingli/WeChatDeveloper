<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Common\AbstractClient;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Protocol\ExternalResourcePolicyInterface;
use We\Common\Request;
use We\Common\Resource;
use We\Common\Transport\EncodedBody;
use We\Common\Transport\HttpTransportInterface;
use We\Common\Transport\Spooler;
use We\Common\Transport\UriBuilder;

/**
 * 微信公众号、小程序和开放平台共用的 Token 与 JSON 响应处理。
 *
 * @internal
 */
abstract class AbstractApiClient extends AbstractClient
{
    /** @param \Closure():string $defaultToken */
    protected function __construct(
        HttpTransportInterface $transport,
        Spooler $spooler,
        private readonly string $endpoint,
        private readonly \Closure $defaultToken,
        ExternalResourcePolicyInterface $resourcePolicy,
    ) {
        parent::__construct($transport, $spooler, resourcePolicy: $resourcePolicy);
    }

    protected function buildRequest(Request $request, EncodedBody $body): RequestInterface
    {
        if ($request->rawMedia) {
            throw new InvalidCallException('`rawMedia()` 仅适用于支付宝 v2 Gateway', channel: $this->channel());
        }
        if ($request->sensitiveKeyId !== null) {
            throw new InvalidCallException('`sensitiveKey()` 仅适用于微信支付', channel: $this->channel());
        }
        if ($request->hasQuery('access_token')) {
            throw new InvalidCallException('`access_token` 必须由调用身份解析', channel: $this->channel());
        }
        if ($request->target instanceof Resource) {
            if (!in_array($request->identity, [Request::IDENTITY_DEFAULT, Request::IDENTITY_ANONYMOUS], true)) {
                throw new InvalidCallException('微信派生资源不能使用业务调用身份', channel: $this->channel());
            }
            $this->assertResource($request->target);

            return $this->httpRequest($request, new Uri($request->target->url), $body);
        }
        $query = $request->query;
        $token = $this->identityToken($request);
        if ($token !== null) {
            $query[] = ['access_token', $token];
        }

        return $this->httpRequest(
            $request,
            UriBuilder::build($this->endpoint, $request->target, $query),
            $body,
            ['Accept' => 'application/json'],
        );
    }

    protected function identityToken(Request $request): ?string
    {
        return match ($request->identity) {
            Request::IDENTITY_ANONYMOUS => null,
            Request::IDENTITY_DEFAULT => ($this->defaultToken)(),
            default => throw new InvalidCallException('当前微信通道不支持该调用身份', channel: $this->channel()),
        };
    }

    protected function assertJsonSuccess(Request $request, ResponseInterface $response, mixed $value): void
    {
        if (is_array($value) && (int)($value['errcode'] ?? 0) !== 0) {
            $code = (int)$value['errcode'];
            throw new PlatformException(
                (string)($value['errmsg'] ?? '微信 API 调用失败'),
                context: [
                    'errcode' => $code,
                    'errmsg' => is_scalar($value['errmsg'] ?? null) ? (string)$value['errmsg'] : null,
                ],
                channel: $this->channel(),
                platformCode: $code,
            );
        }
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '微信 API 返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $this->channel(),
                platformCode: $response->getStatusCode(),
            );
        }
    }

    private function assertResource(Resource $resource): void
    {
        if ($resource->channel !== $this->channel()) {
            throw new InvalidCallException('派生资源不属于当前平台通道', channel: $this->channel());
        }
        if ($resource->signed) {
            throw new InvalidCallException('微信普通平台派生资源不能携带平台凭证', channel: $this->channel());
        }
        $this->resourcePolicy->assertAllowed($this->channel(), $resource);
    }
}
