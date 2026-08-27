<?php

declare(strict_types=1);

namespace We\Common;

use We\Alipay\Common\Internal\NullTokenProvider;
use We\Alipay\Common\TokenProviderInterface;
use We\Common\Exception\ConfigurationException;
use We\Common\Protocol\ExternalResourcePolicyInterface;
use We\Common\Protocol\PublicNetworkResourcePolicy;
use We\Common\Transport\GuzzleTransport;
use We\Common\Transport\HttpTransportInterface;
use We\Common\Transport\Spooler;
use We\Wechat\Common\FileCacheStore;
use We\Wechat\Common\StoreCacheInterface;
use We\Wechat\WxOpen\ComponentTicketProviderInterface;
use We\Wechat\WxOpen\Internal\NullComponentTicketProvider;
use We\Wechat\WxOpen\StoreTokenInterface;

/** 集中配置六个通道 Client 可复用的运行依赖。 */
final class Runtime
{
    public const DEFAULT_CACHE_KEY_PREFIX = 'wechat_developer';

    private ?StoreCacheInterface $resolvedCache = null;

    private ?HttpTransportInterface $resolvedTransport = null;

    private ?Spooler $resolvedSpooler = null;

    /**
     * 配置六个通道共享的可替换运行依赖。
     *
     * 未提供的依赖按需使用本地默认实现；需要支付宝授权身份、微信开放平台
     * ticket 或授权方 Token 时，对应 Provider 或存储必须显式注入。
     */
    public function __construct(
        private readonly ?StoreCacheInterface $cache = null,
        private readonly ?StoreTokenInterface $authorizers = null,
        private readonly ?HttpTransportInterface $transport = null,
        private readonly ?TokenProviderInterface $tokens = null,
        private readonly ?ComponentTicketProviderInterface $componentTickets = null,
        private readonly ?ExternalResourcePolicyInterface $resourcePolicy = null,
        private readonly string $cacheKeyPrefix = self::DEFAULT_CACHE_KEY_PREFIX,
        private readonly ?string $spoolDirectory = null,
    ) {
        if (trim(trim($this->cacheKeyPrefix), ':') === '') {
            throw new ConfigurationException('`cacheKeyPrefix` 不能为空');
        }
    }

    /** @internal */
    public function cache(): StoreCacheInterface
    {
        return $this->resolvedCache ??= $this->cache ?? new FileCacheStore(self::temporaryDirectory('wechat_developer_cache'));
    }

    /** @internal */
    public function authorizers(): ?StoreTokenInterface
    {
        return $this->authorizers;
    }

    /** @internal */
    public function transport(): HttpTransportInterface
    {
        return $this->resolvedTransport ??= $this->transport ?? new GuzzleTransport();
    }

    /** @internal */
    public function tokens(): TokenProviderInterface
    {
        return $this->tokens ?? new NullTokenProvider();
    }

    /** @internal */
    public function componentTickets(): ComponentTicketProviderInterface
    {
        return $this->componentTickets ?? new NullComponentTicketProvider();
    }

    /** @internal */
    public function resourcePolicy(): ExternalResourcePolicyInterface
    {
        return $this->resourcePolicy ?? new PublicNetworkResourcePolicy();
    }

    /** @internal */
    public function cacheKeyPrefix(): string
    {
        return $this->cacheKeyPrefix;
    }

    /** @internal */
    public function spooler(): Spooler
    {
        return $this->resolvedSpooler ??= new Spooler(
            $this->spoolDirectory ?? self::temporaryDirectory('wechat_developer_spool'),
        );
    }

    private static function temporaryDirectory(string $name): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR . $name;
    }
}
