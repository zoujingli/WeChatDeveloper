<?php

declare(strict_types=1);

namespace We;

use GuzzleHttp\ClientInterface;
use We\Config\AlipayPaymentConfig;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Config\WechatWxappConfig;
use We\Contract\ConfigInterface;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;
use We\Exception\SdkException;
use We\Platform\Alipay\PaymentClient as AlipayPaymentClient;
use We\Platform\Alipay\PlatformClient as AlipayPlatformClient;
use We\Platform\Wechat\PaymentClient as WechatPaymentClient;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Platform\Wechat\ServiceClient as WechatServiceClient;
use We\Platform\Wechat\WxappClient as WechatWxappClient;
use We\Support\CacheKey;
use We\Support\FileCacheStore;

/**
 * SDK 根入口：按平台与业务域创建微信、支付宝客户端，工厂方法命名与配置对象语义保持一致。
 *
 * access_token 等运行态数据的缓存键由 cacheKeyPrefix、platformChannel 与 logicalKey 三段编码组成，见 {@see CacheKey::compose}。
 * 未注入缓存实现时使用 {@see FileCacheStore}，默认目录为 {@see self::defaultCacheStoreDirectory()}。
 */
final class Client
{
    /** 未显式传入 `cacheKeyPrefix` 时使用的包级默认通用段 */
    public const DEFAULT_CACHE_KEY_PREFIX = 'wechat_developer';

    /** 未传入 `cache` 时在系统临时目录下使用的子目录名（与 {@see defaultCacheStoreDirectory} 拼接） */
    public const DEFAULT_CACHE_STORE_DIR_NAME = 'wechat_developer_cache';

    private readonly StoreCacheInterface $cache;

    private readonly ?StoreTokenInterface $authorizers;

    private readonly ?ClientInterface $http;

    private readonly string $cacheKeyPrefix;

    /**
     * 初始化 SDK 根入口，注入运行态缓存、授权方 Token 仓库和 HTTP 客户端。
     */
    public function __construct(
        ?StoreCacheInterface $cache = null,
        ?StoreTokenInterface $authorizers = null,
        ?ClientInterface $http = null,
        string $cacheKeyPrefix = self::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        // 缓存前缀是完整缓存键第一段，必须稳定且非空，避免不同业务实例互相覆盖 token。
        $g = trim(trim($cacheKeyPrefix), ':');
        if ($g === '') {
            throw new SdkException('cacheKeyPrefix 不能为空');
        }
        $this->cacheKeyPrefix = $g;
        $this->cache = $cache ?? new FileCacheStore(self::defaultCacheStoreDirectory());
        $this->authorizers = $authorizers;
        $this->http = $http;
    }

    /** 使用微信公众平台配置创建公众平台客户端。 */
    public function wechatPlatform(WechatPlatformConfig $config): WechatPlatformClient
    {
        return new WechatPlatformClient($config, $this->http, $this->cache, $this->cacheKeyPrefix);
    }

    /** 使用微信小程序配置创建小程序客户端。 */
    public function wechatWxapp(WechatWxappConfig $config): WechatWxappClient
    {
        return new WechatWxappClient($config, $this->http, $this->cache, $this->cacheKeyPrefix);
    }

    /** 使用微信服务平台配置创建第三方平台客户端。 */
    public function wechatService(WechatServiceConfig $config): WechatServiceClient
    {
        return new WechatServiceClient($config, $this->http, $this->cache, $this->authorizers, $this->cacheKeyPrefix);
    }

    /** 使用微信支付 APIv3 配置创建支付客户端。 */
    public function wechatPayment(WechatPaymentConfig $config): WechatPaymentClient
    {
        return new WechatPaymentClient($config, $this->http);
    }

    /** 使用支付宝开放平台配置创建平台客户端。 */
    public function alipayPlatform(AlipayPlatformConfig $config): AlipayPlatformClient
    {
        return new AlipayPlatformClient($config, $this->http);
    }

    /** 使用支付宝支付配置创建支付客户端。 */
    public function alipayPayment(AlipayPaymentConfig $config): AlipayPaymentClient
    {
        return new AlipayPaymentClient($config, $this->http);
    }

    /** 默认缓存落盘目录：`sys_get_temp_dir()` + 包级子目录名，供未显式传入 `cache` 时构造 {@see FileCacheStore}。 */
    public static function defaultCacheStoreDirectory(): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . self::DEFAULT_CACHE_STORE_DIR_NAME;
    }

    /**
     * 按字符串通道创建客户端；通道名称与配置对象一一对应。
     */
    public function get(string $channel, ConfigInterface $config): object
    {
        return match ($channel) {
            'wechat.platform' => $this->wechatPlatform($this->ensureConfig($config, WechatPlatformConfig::class, 'wechatPlatform')),
            'wechat.wxapp' => $this->wechatWxapp($this->ensureConfig($config, WechatWxappConfig::class, 'wechatWxapp')),
            'wechat.service' => $this->wechatService($this->ensureConfig($config, WechatServiceConfig::class, 'wechatService')),
            'wechat.payment' => $this->wechatPayment($this->ensureConfig($config, WechatPaymentConfig::class, 'wechatPayment')),
            'alipay.platform' => $this->alipayPlatform($this->ensureConfig($config, AlipayPlatformConfig::class, 'alipayPlatform')),
            'alipay.payment' => $this->alipayPayment($this->ensureConfig($config, AlipayPaymentConfig::class, 'alipayPayment')),
            default => throw new SdkException('不支持的通道标识: ' . $channel),
        };
    }

    /**
     * 校验工厂方法收到的配置对象类型。
     *
     * @template T of object
     * @param class-string<T> $expected
     * @return T
     */
    private function ensureConfig(ConfigInterface $config, string $expected, string $factory): object
    {
        if (!$config instanceof $expected) {
            throw new SdkException($factory . ' 需要 ' . $this->shortClass($expected));
        }

        return $config;
    }

    /**
     * 获取类短名，用于生成清晰的配置类型错误信息。
     *
     * @param class-string $fqcn
     */
    private function shortClass(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
