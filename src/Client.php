<?php

declare(strict_types=1);

namespace We;

use GuzzleHttp\ClientInterface;
use ReflectionClass;
use ReflectionException;
use We\Config\AlipayPaymentConfig;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Config\WechatWxappConfig;
use We\Contract\ConfigInterface;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;
use We\Exception\WechatException;
use We\Platform\Alipay\PaymentClient as AlipayPaymentClient;
use We\Platform\Alipay\PlatformClient as AlipayPlatformClient;
use We\Platform\Wechat\PaymentClient as WechatPaymentClient;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Platform\Wechat\ServiceClient as WechatServiceClient;
use We\Platform\Wechat\WxappClient as WechatWxappClient;
use We\Support\FileCacheStore;

/**
 * 根入口：通过 `__call` 以通道工厂方法名创建各平台客户端（内部 `ReflectionClass::newInstanceArgs`），便于集中维护构造参数。
 *
 * 缓存键固定为 `{cacheKeyPrefix}:{platformChannel}:{logicalKey}`，见 {@see \We\Support\CacheKey::compose}；未传 `cacheKeyPrefix` 时使用 {@see self::DEFAULT_CACHE_KEY_PREFIX}，不得传空白字符串。
 * 未传入 `cache` 时默认使用 {@see FileCacheStore}，目录为 {@see self::defaultCacheStoreDirectory()}（位于 PHP `sys_get_temp_dir()` 下）。
 *
 * @method WechatPlatformClient wechatPlatform(WechatPlatformConfig $config)
 * @method WechatWxappClient    wechatWxapp(WechatWxappConfig $config)
 * @method WechatServiceClient  wechatService(WechatServiceConfig $config)
 * @method WechatPaymentClient  wechatPayment(WechatPaymentConfig $config)
 * @method AlipayPlatformClient alipayPlatform(AlipayPlatformConfig $config)
 * @method AlipayPaymentClient  alipayPayment(AlipayPaymentConfig $config)
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

    public function __construct(
        ?StoreCacheInterface $cache = null,
        ?StoreTokenInterface $authorizers = null,
        ?ClientInterface $http = null,
        string $cacheKeyPrefix = self::DEFAULT_CACHE_KEY_PREFIX,
    ) {
        // 缓存前缀是完整缓存键第一段，必须稳定且非空，避免不同业务实例互相覆盖 token。
        $g = trim(trim($cacheKeyPrefix), ':');
        if ($g === '') {
            throw new WechatException('cacheKeyPrefix 不能为空');
        }
        $this->cacheKeyPrefix = $g;
        $this->cache = $cache ?? new FileCacheStore(self::defaultCacheStoreDirectory());
        $this->authorizers = $authorizers;
        $this->http = $http;
    }

    /** 默认缓存落盘目录：`sys_get_temp_dir()` + 包级子目录名，供未显式传入 `cache` 时构造 {@see FileCacheStore}。 */
    public static function defaultCacheStoreDirectory(): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . self::DEFAULT_CACHE_STORE_DIR_NAME;
    }

    /**
     * 按通道标识取实例：`new Client()->get('wechat.platform', new WechatPlatformConfig(...))`
     */
    public function get(string $channel, ConfigInterface $config): object
    {
        $factory = match ($channel) {
            'wechat.platform' => 'wechatPlatform',
            'wechat.wxapp' => 'wechatWxapp',
            'wechat.service' => 'wechatService',
            'wechat.payment' => 'wechatPayment',
            'alipay.platform' => 'alipayPlatform',
            'alipay.payment' => 'alipayPayment',
            default => throw new WechatException('不支持的通道标识: ' . $channel),
        };

        return $this->__call($factory, [$config]);
    }

    /**
     * 魔术工厂：`$client->wechatPlatform($config)` 等价于反射 `new WechatPlatformClient(...)`。
     *
     * @param array<int, mixed> $arguments
     */
    public function __call(string $name, array $arguments): object
    {
        $config = $arguments[0] ?? null;
        if (!$config instanceof ConfigInterface) {
            throw new WechatException('通道工厂第一个参数必须为 ConfigInterface 对象');
        }

        return match ($name) {
            'wechatPlatform' => $this->instantiate(WechatPlatformClient::class, [
                $this->ensureConfig($config, WechatPlatformConfig::class, 'wechatPlatform'),
                $this->http,
                $this->cache,
                $this->cacheKeyPrefix,
            ]),
            'wechatWxapp' => $this->instantiate(WechatWxappClient::class, [
                $this->ensureConfig($config, WechatWxappConfig::class, 'wechatWxapp'),
                $this->http,
                $this->cache,
                $this->cacheKeyPrefix,
            ]),
            'wechatService' => $this->instantiate(WechatServiceClient::class, [
                $this->ensureConfig($config, WechatServiceConfig::class, 'wechatService'),
                $this->http,
                $this->cache,
                $this->authorizers,
                $this->cacheKeyPrefix,
            ]),
            'wechatPayment' => $this->instantiate(WechatPaymentClient::class, [
                $this->ensureConfig($config, WechatPaymentConfig::class, 'wechatPayment'),
                $this->http,
            ]),
            'alipayPlatform' => $this->instantiate(AlipayPlatformClient::class, [
                $this->ensureConfig($config, AlipayPlatformConfig::class, 'alipayPlatform'),
                $this->http,
            ]),
            'alipayPayment' => $this->instantiate(AlipayPaymentClient::class, [
                $this->ensureConfig($config, AlipayPaymentConfig::class, 'alipayPayment'),
                $this->http,
            ]),
            default => throw new WechatException('不支持的通道工厂方法: ' . $name),
        };
    }

    /**
     * @param class-string $class
     * @param array<int, mixed> $args
     */
    private function instantiate(string $class, array $args): object
    {
        try {
            return (new ReflectionClass($class))->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            throw new WechatException('通道客户端实例化失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $expected
     * @return T
     */
    private function ensureConfig(ConfigInterface $config, string $expected, string $factory): object
    {
        if (!$config instanceof $expected) {
            throw new WechatException($factory . ' 需要 ' . $this->shortClass($expected));
        }

        return $config;
    }

    /** @param class-string $fqcn */
    private function shortClass(string $fqcn): string
    {
        $pos = strrpos($fqcn, '\\');

        return $pos === false ? $fqcn : substr($fqcn, $pos + 1);
    }
}
