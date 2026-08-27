<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

use We\Common\Config\Endpoint;
use We\Common\Exception\ProtocolException;
use We\Common\Transport\UriBuilder;
use We\Wechat\Common\StoreCacheInterface;
use We\Wechat\Common\WeChatTokenStrategy;

/**
 * 管理微信 `standard` 或 `stable` access Token 的缓存与发送前刷新。
 *
 * @internal
 */
final class WeChatTokenManager
{
    public function __construct(
        private readonly StoreCacheInterface $cache,
        private readonly TokenHttpClient $http,
        private readonly string $cachePrefix,
    ) {}

    public function token(
        string $channel,
        string $logicalKey,
        string $appid,
        string $appSecret,
        WeChatTokenStrategy $strategy,
        Endpoint $endpoint,
    ): string {
        $key = CacheKey::compose($this->cachePrefix, $channel, $logicalKey);
        $cached = $this->cache->get($key, '');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $appid, $appSecret, $strategy, $endpoint, $channel): string {
            $cached = $this->cache->get($key, '');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
            $data = $strategy === WeChatTokenStrategy::Stable
                ? $this->stable($endpoint, $appid, $appSecret, $channel)
                : $this->standard($endpoint, $appid, $appSecret, $channel);
            $token = $data['access_token'] ?? null;
            $expires = $data['expires_in'] ?? null;
            if (!is_string($token) || trim($token) === '' || !is_numeric($expires) || (int)$expires <= 0) {
                throw new ProtocolException('微信 access Token 响应无效', context: [
                    'has_access_token' => array_key_exists('access_token', $data),
                    'expires_in' => is_scalar($expires) ? (string)$expires : null,
                ], channel: $channel);
            }
            $this->cache->set($key, $token, max(1, (int)$expires - 300));

            return $token;
        });
    }

    /** @return array<string,mixed> */
    private function standard(Endpoint $endpoint, string $appid, string $secret, string $channel): array
    {
        $uri = UriBuilder::build($endpoint->baseUri, 'cgi-bin/token', [
            ['grant_type', 'client_credential'],
            ['appid', $appid],
            ['secret', $secret],
        ]);

        return $this->http->get($uri, $channel);
    }

    /** @return array<string,mixed> */
    private function stable(Endpoint $endpoint, string $appid, string $secret, string $channel): array
    {
        return $this->http->postJson(
            $endpoint->baseUri . '/cgi-bin/stable_token',
            ['grant_type' => 'client_credential', 'appid' => $appid, 'secret' => $secret],
            $channel,
        );
    }
}
