<?php

declare(strict_types=1);

/**
 * 微信公众平台客户端。
 */

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use We\Client;
use We\Config\WechatPlatformConfig;
use We\Contract\StoreCacheInterface;
use We\Platform\Wechat\Concerns\InteractsProtocol;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\MessageCrypto;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

/**
 * 微信公众平台客户端。
 *
 * 负责获取和缓存微信公众平台接口调用凭据 access_token，向微信公众平台 API 请求自动附加 access_token，并提供消息安全模式加解密能力。
 */
final class PlatformClient
{
    use InteractsProtocol;

    /** 与 {@see \We\Client::get} 通道标识一致，用于 Token 键平台段 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.platform';

    private const API = 'https://api.weixin.qq.com/';

    private const OPEN = 'https://open.weixin.qq.com/';

    private JsonClient $http;

    /**
     * 创建微信公众平台客户端并初始化官方 API HTTP 客户端。
     */
    public function __construct(
        private readonly WechatPlatformConfig $config,
        ?ClientInterface $http = null,
        private readonly StoreCacheInterface $cache = new NullCacheStore(),
        private readonly string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => self::API, 'timeout' => 20.0]));
    }

    /**
     * 获取微信公众平台接口调用凭据 access_token；缓存未命中或强制刷新时调用官方 token 接口。
     */
    public function accessToken(bool $refresh = false): string
    {
        return $this->clientCredentialAccessToken(
            TokenCacheKey::wechatPlatformAccessToken($this->config->appid, $this->config->storageScope),
            $this->config->appid,
            $this->config->appSecret,
            $refresh,
        );
    }

    /**
     * 请求微信公众平台 API；默认自动在 query 中附加 access_token。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): array
    {
        return $this->jsonWechatRequest($method, $uri, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 请求微信公众平台 API 并返回原始响应，适合图片、媒体、文件等非 JSON 接口。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function raw(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): ResponseInterface
    {
        return $this->rawWechatRequest($method, $uri, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 下载微信公众平台二进制资源并返回原始响应。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function download(string $uri, array $query = [], array $options = [], bool $withToken = true): ResponseInterface
    {
        return $this->downloadWechatResource($uri, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 使用 multipart/form-data 上传文件或媒体资源。
     *
     * @param array<int,array<string,mixed>> $multipart
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function upload(string $uri, array $multipart, array $query = [], array $options = [], bool $withToken = true): array
    {
        return $this->uploadWechatResource($uri, $multipart, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 创建微信公众平台消息安全模式加解密工具。
     */
    private function messageCrypto(): MessageCrypto
    {
        return new MessageCrypto($this->config->token, $this->config->encodingAesKey, $this->config->appid);
    }

    /**
     * 通用 API 调用入口：按官方接口 path 与参数发起请求。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $uri = ltrim($uriOrPath, '/');
        $messageCryptoResult = $this->handleWechatMessageCryptoCall($uri, $params);
        if ($messageCryptoResult !== null) {
            return $messageCryptoResult;
        }
        if (in_array($uri, ['connect/oauth2/authorize', 'oauth2/authorize', 'open/oauth2/authorize'], true)) {
            return ['url' => $this->openAuthorizeUrl($params)];
        }
        if (in_array($uri, ['connect/qrconnect', 'qrconnect', 'open/qrconnect'], true)) {
            return ['url' => $this->openQrconnectUrl($params)];
        }

        return $this->callWechatJsonApi($uri, $params, $httpMethod, $options, true, ['with_token']);
    }

    /**
     * 按 POST 方法调用微信公众平台 API。
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
     * 按 GET 方法调用微信公众平台 API。
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
     * 生成微信网页授权地址（open.weixin.qq.com/connect/oauth2/authorize）。
     *
     * @param array<string,mixed> $params
     */
    private function openAuthorizeUrl(array $params): string
    {
        return $this->openConnectUrl('connect/oauth2/authorize', array_merge([
            'appid' => $this->config->appid,
            'redirect_uri' => '',
            'response_type' => 'code',
            'scope' => 'snsapi_base',
            'state' => '',
        ], $params));
    }

    /**
     * 生成微信网站应用扫码登录地址（open.weixin.qq.com/connect/qrconnect）。
     *
     * @param array<string,mixed> $params
     */
    private function openQrconnectUrl(array $params): string
    {
        return $this->openConnectUrl('connect/qrconnect', array_merge([
            'appid' => $this->config->appid,
            'redirect_uri' => '',
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => '',
        ], $params));
    }

    /**
     * 拼接 open.weixin.qq.com 授权类地址，统一追加微信重定向片段。
     *
     * @param array<string,mixed> $query
     */
    private function openConnectUrl(string $path, array $query): string
    {
        return self::OPEN . $path . '?' . http_build_query($query) . '#wechat_redirect';
    }

    /**
     * 生成当前微信公众平台通道下的完整缓存键。
     */
    private function cacheKey(string $logicalKey): string
    {
        return CacheKey::compose($this->cacheKeyPrefix, self::TOKEN_PLATFORM_CHANNEL, $logicalKey);
    }
}
