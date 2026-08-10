<?php

declare(strict_types=1);

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use We\Client;
use We\Config\WechatServiceConfig;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;
use We\Contract\Trait\WechatInteractsProtocol;
use We\Exception\WechatException;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\MessageCrypto;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

/**
 * 微信服务平台（第三方平台）客户端。
 *
 * 负责第三方平台 component_access_token 缓存、授权方 authorizer_access_token 刷新和代授权方调用微信公众平台或小程序接口。
 */
final class ServiceClient
{
    use WechatInteractsProtocol;

    /** 与 {@see Client::get} 通道标识一致 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.service';

    private JsonClient $http;

    private readonly ?StoreTokenInterface $authorizers;

    /**
     * 创建微信服务平台（第三方平台）客户端并初始化官方 API HTTP 客户端。
     */
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

    /**
     * 获取第三方平台接口调用凭据 component_access_token；缓存未命中或强制刷新时调用官方 component_token 接口。
     */
    public function componentAccessToken(string $componentVerifyTicket, bool $refresh = false): string
    {
        $key = $this->cacheKey(TokenCacheKey::wechatServiceComponentAccessToken($this->config->componentAppid, $this->config->storageScope));
        if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $componentVerifyTicket, $refresh): string {
            if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $data = $this->jsonWechatRequest('POST', 'cgi-bin/component/api_component_token', [], [
                'json' => [
                    'component_appid' => $this->config->componentAppid,
                    'component_appsecret' => $this->config->componentAppSecret,
                    'component_verify_ticket' => $componentVerifyTicket,
                ],
            ]);
            $token = $this->wechatTokenValue($data, 'component_access_token', '微信 component_access_token');
            $this->cache->set($key, $token, $this->wechatTokenTtl($data, '微信 component_access_token'));

            return $token;
        });
    }

    /**
     * 请求微信服务平台（第三方平台） API。
     *
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = []): array
    {
        return $this->jsonWechatRequest($method, $uri, $query, $options);
    }

    /**
     * 请求微信服务平台 API 并返回原始响应，适合图片、媒体、文件等非 JSON 接口。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function raw(string $method, string $uri, array $query = [], array $options = []): ResponseInterface
    {
        return $this->rawWechatRequest($method, $uri, $query, $options);
    }

    /**
     * 下载微信服务平台二进制资源并返回原始响应。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function download(string $uri, array $query = [], array $options = []): ResponseInterface
    {
        return $this->downloadWechatResource($uri, $query, $options);
    }

    /**
     * 使用 multipart/form-data 上传文件或媒体资源。
     *
     * @param array<int,array<string,mixed>> $multipart
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function upload(string $uri, array $multipart, array $query = [], array $options = []): array
    {
        return $this->uploadWechatResource($uri, $multipart, $query, $options);
    }

    /**
     * 调用创建预授权码接口，生成 pre_auth_code。
     *
     * @return array<string,mixed>
     */
    public function createPreAuthCode(string $componentAccessToken): array
    {
        return $this->request('POST', 'cgi-bin/component/api_create_preauthcode', ['component_access_token' => $componentAccessToken], [
            'json' => ['component_appid' => $this->config->componentAppid],
        ]);
    }

    /**
     * 生成第三方平台授权登录页地址。
     */
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

    /**
     * 使用 authorization_code 调用查询授权信息接口。
     *
     * @return array<string,mixed>
     */
    public function queryAuth(string $componentAccessToken, string $authorizationCode): array
    {
        return $this->request('POST', 'cgi-bin/component/api_query_auth', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorization_code' => $authorizationCode,
            ],
        ]);
    }

    /**
     * 调用获取授权方账号基本信息接口。
     *
     * @return array<string,mixed>
     */
    public function authorizerInfo(string $componentAccessToken, string $authorizerAppid): array
    {
        return $this->request('POST', 'cgi-bin/component/api_get_authorizer_info', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorizer_appid' => $authorizerAppid,
            ],
        ]);
    }

    /**
     * 使用授权方 authorizer_access_token 代调用微信公众平台或小程序接口。
     *
     * @return array<string,mixed>
     */
    public function requestAsAuthorizer(string $method, string $uri, string $authorizerAppid, string $componentAccessToken, array $query = [], array $options = []): array
    {
        $query['access_token'] = $this->authorizerAccessToken($componentAccessToken, $authorizerAppid);

        return $this->jsonWechatRequest($method, $uri, $query, $options);
    }

    /**
     * 通用 API 调用入口：按官方接口 path、授权方上下文和参数发起请求。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $method = $this->normalizeWechatHttpMethod($httpMethod);
        $uri = ltrim($uriOrPath, '/');
        $messageCryptoResult = $this->handleWechatMessageCryptoCall($uri, $params);
        if ($messageCryptoResult !== null) {
            return $messageCryptoResult;
        }
        if (isset($options['authorizer_appid'], $options['component_access_token'])) {
            return $this->requestAsAuthorizer(
                $method,
                $uri,
                (string)$options['authorizer_appid'],
                (string)$options['component_access_token'],
                $this->wechatCallQuery($method, $params, $options),
                $this->buildWechatJsonOptions($method, $params, $options, ['authorizer_appid', 'component_access_token']),
            );
        }

        return $this->callWechatJsonApi($uri, $params, $httpMethod, $options, false, ['authorizer_appid', 'component_access_token']);
    }

    /**
     * 按 POST 方法调用微信服务平台 API。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * 按 GET 方法调用微信服务平台 API。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }

    /**
     * 创建第三方平台授权事件消息加解密工具。
     */
    private function messageCrypto(): MessageCrypto
    {
        return new MessageCrypto($this->config->componentToken, $this->config->componentEncodingAesKey, $this->config->componentAppid);
    }

    /**
     * 获取授权方接口调用凭据 authorizer_access_token；缓存未命中时使用 authorizer_refresh_token 刷新。
     */
    private function authorizerAccessToken(string $componentAccessToken, string $authorizerAppid): string
    {
        $authorizers = $this->authorizers;
        if (!$authorizers) {
            throw new WechatException('未配置授权账号 Token 仓库');
        }
        $key = $this->cacheKey(TokenCacheKey::wechatServiceAuthorizerAccessToken(
            $this->config->componentAppid,
            $authorizerAppid,
            $this->config->storageScope,
        ));
        if (is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $componentAccessToken, $authorizerAppid, $authorizers): string {
            if (is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $refreshToken = $authorizers->refreshToken($authorizerAppid);
            if (trim($refreshToken) === '') {
                throw new WechatException('授权方 authorizer_refresh_token 不能为空');
            }
            $data = $this->refreshAuthorizerToken($componentAccessToken, $authorizerAppid, $refreshToken);
            $authorizers->saveAuthorizerToken($authorizerAppid, $data);
            $token = $this->wechatTokenValue($data, 'authorizer_access_token', '微信 authorizer_access_token');
            $this->cache->set($key, $token, $this->wechatTokenTtl($data, '微信 authorizer_access_token'));

            return $token;
        });
    }

    /**
     * 调用刷新授权方接口调用凭据接口。
     *
     * @return array<string,mixed>
     */
    private function refreshAuthorizerToken(string $componentAccessToken, string $authorizerAppid, string $refreshToken): array
    {
        return $this->jsonWechatRequest('POST', 'cgi-bin/component/api_authorizer_token', ['component_access_token' => $componentAccessToken], [
            'json' => [
                'component_appid' => $this->config->componentAppid,
                'authorizer_appid' => $authorizerAppid,
                'authorizer_refresh_token' => $refreshToken,
            ],
        ]);
    }

    /**
     * 生成当前第三方平台通道下的完整缓存键。
     */
    private function cacheKey(string $logicalKey): string
    {
        return CacheKey::compose($this->cacheKeyPrefix, self::TOKEN_PLATFORM_CHANNEL, $logicalKey);
    }
}
