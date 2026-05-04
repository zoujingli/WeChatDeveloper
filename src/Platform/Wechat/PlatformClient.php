<?php

declare(strict_types=1);

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use We\Client;
use We\Config\WechatPlatformConfig;
use We\Contract\StoreCacheInterface;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\MessageCrypto;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

final class PlatformClient
{
    /** 与 {@see \We\Client::get} 通道标识一致，用于 Token 键平台段 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.platform';

    private const API = 'https://api.weixin.qq.com/';

    private JsonClient $http;

    public function __construct(
        private readonly WechatPlatformConfig $config,
        ?ClientInterface $http = null,
        private readonly StoreCacheInterface $cache = new NullCacheStore(),
        private readonly string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => self::API, 'timeout' => 20.0]));
    }

    public function accessToken(bool $refresh = false): string
    {
        $key = $this->cacheKey(TokenCacheKey::wechatOfficialAccessToken($this->config->appid, $this->config->storageScope));
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
            $token = (string)($data['access_token'] ?? '');
            $this->cache->set($key, $token, max(1, (int)($data['expires_in'] ?? 7200) - 300));

            return $token;
        });
    }

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): array
    {
        if ($withToken) {
            $query['access_token'] = $query['access_token'] ?? $this->accessToken();
        }

        return $this->http->request($method, ltrim($uri, '/'), $query, $options);
    }

    /** @param array<string,mixed> $menu @return array<string,mixed> */
    public function createMenu(array $menu): array
    {
        return $this->request('POST', 'cgi-bin/menu/create', options: ['json' => $menu]);
    }

    /** @return array<string,mixed> */
    public function userList(string $nextOpenid = ''): array
    {
        return $this->request('GET', 'cgi-bin/user/get', ['next_openid' => $nextOpenid]);
    }

    /**
     * @param array<int,string> $openids
     * @return array<string,mixed>
     */
    public function batchUserInfo(array $openids, string $lang = 'zh_CN'): array
    {
        return $this->request('POST', 'cgi-bin/user/info/batchget', options: [
            'json' => [
                'user_list' => array_map(static fn (string $openid): array => ['openid' => $openid, 'lang' => $lang], $openids),
            ],
        ]);
    }

    public function messageCrypto(): MessageCrypto
    {
        return new MessageCrypto($this->config->token, $this->config->encodingAesKey, $this->config->appid);
    }

    /**
     * 统一调用入口：除 token 基础接口外，默认按 URI + 参数发起请求。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $method = strtoupper($httpMethod === '' ? 'POST' : $httpMethod);
        $uri = ltrim($uriOrPath, '/');
        if ($uri === 'decrypt_message') {
            return $this->messageCrypto()->decryptMessage(
                (string)($params['body'] ?? ''),
                (string)($params['msg_signature'] ?? ''),
                (string)($params['timestamp'] ?? ''),
                (string)($params['nonce'] ?? ''),
            );
        }
        if ($uri === 'encrypt_message') {
            return [
                'xml' => $this->messageCrypto()->encryptMessage(
                    (string)($params['body'] ?? ''),
                    (string)($params['timestamp'] ?? time()),
                    (string)($params['nonce'] ?? ''),
                ),
            ];
        }

        return $this->request(
            $method,
            $uri,
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
        if ($method === 'GET' || isset($options['json']) || isset($options['body']) || isset($options['form_params']) || isset($options['multipart'])) {
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
