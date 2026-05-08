<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Support;

use We\Contract\StoreCacheInterface;

/**
 * 空缓存实现：用于测试或显式禁用缓存；读取永远返回默认值，锁回调直接执行。
 */
final class NullCacheStore implements StoreCacheInterface
{
    /**
     * 返回默认值，不保存任何缓存数据。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    /**
     * 忽略写入请求。
     */
    public function set(string $key, mixed $value, int $ttl): void {}

    /**
     * 忽略删除请求。
     */
    public function del(string $key): void {}

    /**
     * 不加锁，直接执行回调。
     */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        return $callback();
    }
}
