<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use We\Common\Config\Endpoint;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Transport\HttpTransportInterface;
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
        private readonly HttpTransportInterface $transport,
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
                ? $this->stable($endpoint, $appid, $appSecret)
                : $this->standard($endpoint, $appid, $appSecret);
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
    private function standard(Endpoint $endpoint, string $appid, string $secret): array
    {
        $uri = UriBuilder::build($endpoint->baseUri, 'cgi-bin/token', [
            ['grant_type', 'client_credential'],
            ['appid', $appid],
            ['secret', $secret],
        ]);

        return $this->request(new Request('GET', $uri));
    }

    /** @return array<string,mixed> */
    private function stable(Endpoint $endpoint, string $appid, string $secret): array
    {
        $json = json_encode(['grant_type' => 'client_credential', 'appid' => $appid, 'secret' => $secret], JSON_THROW_ON_ERROR);

        return $this->request(new Request('POST', rtrim($endpoint->baseUri, '/') . '/cgi-bin/stable_token', [
            'Content-Type' => 'application/json',
        ], Utils::streamFor($json)));
    }

    /** @return array<string,mixed> */
    private function request(Request $request): array
    {
        $response = $this->transport->send($request);
        $body = (string)$response->getBody();
        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ProtocolException('微信 Token 响应不是有效 JSON', 0, $exception);
        }
        if (!is_array($data)) {
            throw new ProtocolException('微信 Token 响应结构无效');
        }
        if ((int)($data['errcode'] ?? 0) != 0) {
            throw new PlatformException(
                (string)($data['errmsg'] ?? '微信 Token 获取失败'),
                context: [
                    'errcode' => (int)$data['errcode'],
                    'errmsg' => is_scalar($data['errmsg'] ?? null) ? (string)$data['errmsg'] : null,
                ],
                platformCode: (int)$data['errcode'],
            );
        }

        return $data;
    }
}
