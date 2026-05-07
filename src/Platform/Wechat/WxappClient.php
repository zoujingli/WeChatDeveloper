<?php

declare(strict_types=1);

/**
 * 微信小程序客户端。
 */

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use We\Client;
use We\Config\WechatWxappConfig;
use We\Contract\StoreCacheInterface;
use We\Platform\Wechat\Concerns\InteractsProtocol;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\NullCacheStore;
use We\Support\TokenCacheKey;

/**
 * 微信小程序客户端。
 *
 * 负责获取和缓存小程序接口调用凭据 access_token，并向小程序 API 请求自动附加 access_token。
 */
final class WxappClient
{
    use InteractsProtocol;

    /** 与 {@see \We\Client::get} 通道标识一致 */
    private const TOKEN_PLATFORM_CHANNEL = 'wechat.wxapp';
    private JsonClient $http;

    /**
     * 创建微信小程序客户端并初始化官方 API HTTP 客户端。
     */
    public function __construct(
        private readonly WechatWxappConfig $config,
        ?ClientInterface $http = null,
        private readonly StoreCacheInterface $cache = new NullCacheStore(),
        private readonly string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => 'https://api.weixin.qq.com/', 'timeout' => 20.0]));
    }

    /**
     * 获取小程序接口调用凭据 access_token；缓存未命中或强制刷新时调用官方 token 接口。
     */
    public function accessToken(bool $refresh = false): string
    {
        return $this->clientCredentialAccessToken(
            TokenCacheKey::wechatWxappAccessToken($this->config->appid, $this->config->storageScope),
            $this->config->appid,
            $this->config->appSecret,
            $refresh,
        );
    }

    /**
     * 请求微信小程序 API；默认自动在 query 中附加 access_token。
     *
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): array
    {
        return $this->jsonWechatRequest($method, $uri, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 请求微信小程序 API 并返回原始响应，适合图片、媒体、文件等非 JSON 接口。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function raw(string $method, string $uri, array $query = [], array $options = [], bool $withToken = true): ResponseInterface
    {
        return $this->rawWechatRequest($method, $uri, $this->withAccessToken($query, $withToken), $options);
    }

    /**
     * 下载微信小程序二进制资源并返回原始响应。
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
     * 通用 API 调用入口：按官方接口 path 与参数发起请求。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        return $this->callWechatJsonApi($uriOrPath, $params, $httpMethod, $options, true, ['with_token']);
    }

    /**
     * 按 POST 方法调用微信小程序 API。
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
     * 按 GET 方法调用微信小程序 API。
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
     * 生成当前小程序通道下的完整缓存键。
     */
    private function cacheKey(string $logicalKey): string
    {
        return CacheKey::compose($this->cacheKeyPrefix, self::TOKEN_PLATFORM_CHANNEL, $logicalKey);
    }
}
