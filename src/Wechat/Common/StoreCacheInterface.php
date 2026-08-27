<?php

declare(strict_types=1);

namespace We\Wechat\Common;

use We\Common\Exception\SdkException;

/** 保存平台 Token 并提供 TTL 与刷新锁的缓存接口。 */
interface StoreCacheInterface
{
    /**
     * 缓存不存在、已过期或无法解析时返回默认值。
     *
     * @throws SdkException 缓存后端不可用
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 写入缓存值；TTL 单位为秒，最小有效值为 1。
     *
     * @throws SdkException 缓存后端拒绝写入
     */
    public function set(string $key, mixed $value, int $ttl): void;

    /**
     * 删除缓存值。
     *
     * @throws SdkException 缓存后端拒绝删除
     */
    public function del(string $key): void;

    /**
     * 在互斥锁内执行回调，避免并发刷新同一平台 Token；锁 TTL 单位为秒。
     *
     * @template T
     * @param callable():T $callback
     * @return T
     * @throws SdkException 无法获取刷新锁
     * @throws \Throwable 回调异常按原类型向上传递
     */
    public function lock(string $key, int $ttl, callable $callback): mixed;
}
