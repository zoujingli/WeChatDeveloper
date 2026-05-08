<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Contract;

/**
 * 运行态缓存契约：用于保存 access_token、component_access_token 等平台接口调用凭据，必须支持 TTL 与刷新锁。
 */
interface StoreCacheInterface
{
    /**
     * 读取缓存值；缓存不存在、已过期或无法解析时返回默认值。
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 写入缓存值；TTL 单位为秒，实现侧至少按 1 秒处理。
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * 删除缓存值。
     */
    public function del(string $key): void;

    /**
     * 在互斥锁内执行回调，避免多进程或集群环境重复刷新平台接口调用凭据。
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public function lock(string $key, int $ttl, callable $callback): mixed;
}
