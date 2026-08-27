<?php

declare(strict_types=1);

namespace We\Wechat\WxOpen\Internal;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Transport\HttpTransportInterface;
use We\Wechat\Common\Internal\CacheKey;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\Common\StoreCacheInterface;
use We\Wechat\WxOpen\ComponentTicketProviderInterface;
use We\Wechat\WxOpen\StoreTokenInterface;
use We\Wechat\WxOpenConfig;

/**
 * 管理微信开放平台 component Token 与 authorizer Token 的生命周期。
 *
 * @internal
 */
final class WxOpenTokenManager
{
    public function __construct(
        private readonly WxOpenConfig $config,
        private readonly StoreCacheInterface $cache,
        private readonly HttpTransportInterface $transport,
        private readonly ComponentTicketProviderInterface $tickets,
        private readonly ?StoreTokenInterface $authorizers,
        private readonly string $cachePrefix,
    ) {}

    public function componentToken(): string
    {
        $logical = TokenCacheKey::wechatOpenComponentAccessToken($this->config->componentAppid, $this->config->storageScope);
        $key = CacheKey::compose($this->cachePrefix, 'wechat.service', $logical);

        return $this->cached($key, fn (): array => $this->post('cgi-bin/component/api_component_token', [
            'component_appid' => $this->config->componentAppid,
            'component_appsecret' => $this->config->componentAppSecret,
            'component_verify_ticket' => $this->tickets->ticket($this->config->componentAppid),
        ]), 'component_access_token');
    }

    public function authorizerToken(string $authorizerAppid): string
    {
        $logical = TokenCacheKey::wechatOpenAuthorizerAccessToken(
            $this->config->componentAppid,
            $authorizerAppid,
            $this->config->storageScope,
        );
        $key = CacheKey::compose($this->cachePrefix, 'wechat.service', $logical);

        return $this->cached($key, function () use ($authorizerAppid): array {
            if (!$this->authorizers instanceof StoreTokenInterface) {
                throw new ConfigurationException('当前通道未配置授权方 Token 存储');
            }
            $refreshToken = $this->authorizers->refreshToken($authorizerAppid);
            if (trim($refreshToken) === '') {
                throw new ConfigurationException('授权方 refresh Token 不能为空');
            }
            $data = $this->post(
                'cgi-bin/component/api_authorizer_token?component_access_token=' . rawurlencode($this->componentToken()),
                [
                    'component_appid' => $this->config->componentAppid,
                    'authorizer_appid' => $authorizerAppid,
                    'authorizer_refresh_token' => $refreshToken,
                ],
            );
            $this->tokenValue($data, 'authorizer_access_token');
            $this->ttl($data);
            $this->authorizers->saveAuthorizerToken($authorizerAppid, $data);

            return $data;
        }, 'authorizer_access_token');
    }

    /** @param \Closure():array<string,mixed> $refresh */
    private function cached(string $key, \Closure $refresh, string $field): string
    {
        $cached = $this->cache->get($key, '');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $refresh, $field): string {
            $cached = $this->cache->get($key, '');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
            $data = $refresh();
            $token = $this->tokenValue($data, $field);
            $this->cache->set($key, $token, $this->ttl($data));

            return $token;
        });
    }

    /** @param array<string,mixed> $data */
    private function tokenValue(array $data, string $field): string
    {
        $token = $data[$field] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new ProtocolException('微信 Token 响应缺少 `' . $field . '`', context: [
                'field' => $field,
                'present' => array_key_exists($field, $data),
            ], channel: 'wechat.service');
        }

        return $token;
    }

    /** @param array<string,mixed> $data */
    private function ttl(array $data): int
    {
        $expires = $data['expires_in'] ?? null;
        if (!is_numeric($expires) || (int)$expires <= 0) {
            throw new ProtocolException('微信 Token 响应的 `expires_in` 无效', context: [
                'expires_in' => is_scalar($expires) ? (string)$expires : null,
            ], channel: 'wechat.service');
        }

        return max(1, (int)$expires - 300);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function post(string $path, array $payload): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $uri = rtrim($this->config->endpoint->baseUri, '/') . '/' . ltrim($path, '/');
        $response = $this->transport->send(new Request('POST', $uri, [
            'Content-Type' => 'application/json',
        ], Utils::streamFor($json)));
        try {
            $data = json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ProtocolException('微信开放平台 Token 响应不是有效 JSON', 0, $exception);
        }
        if (!is_array($data)) {
            throw new ProtocolException('微信开放平台 Token 响应结构无效');
        }
        if ((int)($data['errcode'] ?? 0) != 0) {
            throw new PlatformException(
                (string)($data['errmsg'] ?? '微信开放平台 Token 获取失败'),
                context: [
                    'errcode' => (int)$data['errcode'],
                    'errmsg' => is_scalar($data['errmsg'] ?? null) ? (string)$data['errmsg'] : null,
                ],
                channel: 'wechat.service',
                platformCode: (int)$data['errcode'],
            );
        }

        return $data;
    }
}
