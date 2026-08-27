<?php

declare(strict_types=1);

namespace We\Tests;

use We\Wechat\Common\StoreCacheInterface;

/**
 * 测试使用的内存 Token 缓存。
 *
 * @internal
 */
final class MemoryCache implements StoreCacheInterface
{
    /** @var array<string,mixed> */
    public array $values = [];

    public int $lockCalls = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->values[$key] = $value;
    }

    public function del(string $key): void
    {
        unset($this->values[$key]);
    }

    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        ++$this->lockCalls;

        return $callback();
    }
}
