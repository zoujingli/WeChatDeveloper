<?php

declare(strict_types=1);

namespace We\Support;

use We\Contract\StoreCacheInterface;

/**
 * 空缓存实现：用于测试或显式禁用缓存，锁回调直接执行。
 */
final class NullCacheStore implements StoreCacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, int $ttl): void {}

    public function del(string $key): void {}

    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        return $callback();
    }
}
