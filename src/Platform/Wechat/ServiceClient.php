<?php

declare(strict_types=1);

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use We\Client;
use We\Config\WechatServiceConfig;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;
use We\Exception\WechatException;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\MessageCrypto;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

final class ServiceClient
{
    /** 与 {@see \We\Client::get} 通道标识一致 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.service';
    private JsonClient $http;

    public function __construct(
        private readonly WechatServiceConfig $config,
        ?ClientInterface $http = null,
        private readonly StoreCacheInterface $cache = new NullCacheStore(),
        ?StoreTokenInterface $authorizers = null,
        private readonly string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => 'https://api.weixin.qq.com/', 'timeout' => 20.0]));
        $this->authorizers = $authorizers;
    }

    private readonly ?StoreTokenInterface $authorizers;

    public function componentAccessToken(string $componentVerifyTicket, bool $refresh = false): string
    {
        $key = $this->cacheKey(TokenCacheKey::wechatOpenComponentAccessToken($this->config->componentAppid, $this->config->storageScope));
        if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $componentVerifyTicket, $refresh): string {
            if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $data = $this->http->request('POST', 'cgi-bin/component/api_component_token', options: [
                'json' => [
                    'component_appid' => $this->config->componentAppid,
                    'component_appsecret' => $this->config->componentAppSecret,
                    'component_verify_ticket' => $componentVerifyTicket,
                ],
            ]);
            $token = (string)$data['component_access_token'];
            $this->cache->set($key, $token, max(1, (int)($data['expires_in'] ?? 7200) - 300));

            return $token;
        });
    }

    /** @return array<string,mixed> */
    public function request(string $method, string $uri, array $query = [], array $options = []): array
    {
        return $this->http->request($method, ltrim($uri, '/'), $query, $options);
    }

    /** @return array<string,mixed> */
    public function createPreAuthCode(string $componentAccessToken): array
    {
        return $this->http->request('POST', 'cgi-bin/component/api_create_preauthcode', ['component_access_token' => $componentAccessToken], [
            'json' => ['component_appid' => $this->config->componentAppid],
        ]);
    }

    public function authorizationUrl(string $preAuthCode, string $redirectUri, int $authType = 3, string $state = ''): string
    {
        return 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?' . http_build_query([
            'component_appid' => $this->config->componentAppid,
            'pre_auth_code' => $preAuthCode,
            'redirect_uri' => $redirectUri,
            'auth_type' => $authType,
            'biz_appid' => '',
            'state' => $state,
        ]);
    }

    /** @return array<string,mixed> */
    public function queryAuth(string $componentAccessToken, string $authorizationCode): array
    {
        return $this->http->request('POST', 'cgi-bin/component/api_query_auth', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorization_code' => $authorizationCode,
            ],
        ]);
    }

    /** @return array<string,mixed> */
    public function authorizerInfo(string $componentAccessToken, string $authorizerAppid): array
    {
        return $this->http->request('POST', 'cgi-bin/component/api_get_authorizer_info', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorizer_appid' => $authorizerAppid,
            ],
        ]);
    }

    /** @return array<string,mixed> */
    public function requestAsAuthorizer(string $method, string $uri, string $authorizerAppid, string $componentAccessToken, array $query = [], array $options = []): array
    {
        $query['access_token'] = $this->authorizerAccessToken($componentAccessToken, $authorizerAppid);

        return $this->http->request($method, ltrim($uri, '/'), $query, $options);
    }

    public function messageCrypto(): MessageCrypto
    {
        return new MessageCrypto($this->config->componentToken, $this->config->componentEncodingAesKey, $this->config->componentAppid);
    }

    /**
     * 统一调用入口：除 token 基础接口外，其他能力默认按 URI + 参数执行。
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
        if (isset($options['authorizer_appid'], $options['component_access_token'])) {
            return $this->requestAsAuthorizer(
                $method,
                $uri,
                (string)$options['authorizer_appid'],
                (string)$options['component_access_token'],
                is_array($options['query'] ?? null) ? $options['query'] : [],
                $this->buildOptions($method, $params, $options),
            );
        }

        return $this->request(
            $method,
            $uri,
            $method === 'GET' ? $params : (is_array($options['query'] ?? null) ? $options['query'] : []),
            $this->buildOptions($method, $params, $options),
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

    private function authorizerAccessToken(string $componentAccessToken, string $authorizerAppid): string
    {
        if (!$this->authorizers) {
            throw new WechatException('未配置授权账号 Token 仓库');
        }
        $key = $this->cacheKey(TokenCacheKey::wechatOpenAuthorizerAccessToken(
            $this->config->componentAppid,
            $authorizerAppid,
            $this->config->storageScope,
        ));
        if (is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $componentAccessToken, $authorizerAppid): string {
            if (is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $data = $this->refreshAuthorizerToken($componentAccessToken, $authorizerAppid, $this->authorizers->refreshToken($authorizerAppid));
            $this->authorizers->saveAuthorizerToken($authorizerAppid, $data);
            $token = (string)$data['authorizer_access_token'];
            $this->cache->set($key, $token, max(1, (int)($data['expires_in'] ?? 7200) - 300));

            return $token;
        });
    }

    /** @return array<string,mixed> */
    private function refreshAuthorizerToken(string $componentAccessToken, string $authorizerAppid, string $refreshToken): array
    {
        return $this->http->request('POST', 'cgi-bin/component/api_authorizer_token', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorizer_appid' => $authorizerAppid,
                'authorizer_refresh_token' => $refreshToken,
            ],
        ]);
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
