<?php

declare(strict_types=1);

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use We\Client;
use We\Config\WechatWxappConfig;
use We\Contract\StoreCacheInterface;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

final class WxappClient
{
    /** 与 {@see \We\Client::get} 通道标识一致 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.wxapp';
    private JsonClient $http;

    public function __construct(
        private readonly WechatWxappConfig $config,
        ?ClientInterface $http = null,
        private readonly StoreCacheInterface $cache = new NullCacheStore(),
        private readonly string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => 'https://api.weixin.qq.com/', 'timeout' => 20.0]));
    }

    public function accessToken(bool $refresh = false): string
    {
        $key = $this->cacheKey(TokenCacheKey::wechatMiniAccessToken($this->config->appid, $this->config->storageScope));
        if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $refresh): string {
            if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $data = $this->http->request('GET', 'cgi-bin/token', [
                'grant_type' => 'client_credential',
                'appid' => $this->config->appid,
                'secret' => $this->config->appSecret,
            ]);
            $token = (string)$data['access_token'];
            $this->cache->set($key, $token, max(1, (int)($data['expires_in'] ?? 7200) - 300));

            return $token;
        });
    }

    /**
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): array
    {
        if ($withToken) {
            $query['access_token'] = $query['access_token'] ?? $this->accessToken();
        }

        return $this->http->request($method, ltrim($uri, '/'), $query, $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $method = strtoupper($httpMethod === '' ? 'POST' : $httpMethod);

        return $this->request(
            $method,
            ltrim($uriOrPath, '/'),
            $method === 'GET' ? $params : (is_array($options['query'] ?? null) ? $options['query'] : []),
            $this->buildOptions($method, $params, $options),
            (bool)($options['with_token'] ?? true),
        );
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function buildOptions(string $method, array $params, array $options): array
    {
        if ($method === 'GET' || isset($options['json']) || isset($options['body']) || isset($options['form_params'])) {
            return $options;
        }
        $options['json'] = $params;

        return $options;
    }

    private function cacheKey(string $logicalKey): string
    {
        return CacheKey::compose($this->cacheKeyPrefix, self::TOKEN_PLATFORM_CHANNEL, $logicalKey);
    }
}
