<?php

declare(strict_types=1);

namespace We\Support;

use Psr\SimpleCache\CacheInterface;
use We\Contract\StoreCacheInterface;
use We\Exception\WechatException;

/**
 * PSR-16 缓存适配器；集群锁需由业务注入原子锁回调。
 */
final class PsrSimpleCacheStore implements StoreCacheInterface
{
    /**
     * @param null|callable(string,int,callable):mixed $locker
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly mixed $locker = null,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->cache->set($key, $value, max(1, $ttl));
    }

    public function del(string $key): void
    {
        $this->cache->delete($key);
    }

    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        if (!is_callable($this->locker)) {
            throw new WechatException('PsrSimpleCacheStore 未配置锁能力');
        }

        return ($this->locker)($key, max(1, $ttl), $callback);
    }
}
