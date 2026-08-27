<?php

declare(strict_types=1);

namespace We\Wechat\Common;

/** 用于测试或显式禁用缓存，不提供互斥语义。 */
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
