<?php

declare(strict_types=1);

namespace We\Contract;

/**
 * 通用缓存契约：用于 access_token 等 SDK 运行态数据，必须显式支持 TTL 与刷新锁。
 */
interface StoreCacheInterface
{
    /**
     * 读取缓存值，缓存不存在、已过期或无法解析时返回默认值。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 写入缓存值；$ttl 为剩余秒数，必须至少按 1 秒处理。
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * 删除缓存值。
     */
    public function del(string $key): void;

    /**
     * 在锁内执行回调，避免集群或多进程下重复刷新 access_token。
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function lock(string $key, int $ttl, callable $callback): mixed;
}
