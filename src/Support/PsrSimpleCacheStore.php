<?php

declare(strict_types=1);

namespace We\Support;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use We\Contract\StoreCacheInterface;
use We\Exception\SdkException;

/**
 * PSR-16 缓存适配器；缓存能力委托给 PSR Simple Cache，实现侧需注入原子锁回调以支持刷新锁。
 */
final class PsrSimpleCacheStore implements StoreCacheInterface
{
    /**
     * 创建 PSR-16 缓存适配器，并可选注入锁回调。
     *
     * @param null|callable(string,int,callable):mixed $locker
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly mixed $locker = null,
    ) {}

    /**
     * 从 PSR-16 缓存读取值。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->execute('get', $key, fn (): mixed => $this->cache->get($key, $default));
    }

    /**
     * 写入 PSR-16 缓存并设置 TTL。
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        $success = $this->execute('set', $key, fn (): bool => $this->cache->set($key, $value, max(1, $ttl)));
        if ($success !== true) {
            throw new SdkException('PsrSimpleCacheStore 写入失败', 0, null, ['key' => $key, 'operation' => 'set']);
        }
    }

    /**
     * 从 PSR-16 缓存删除值。
     */
    public function del(string $key): void
    {
        $success = $this->execute('delete', $key, fn (): bool => $this->cache->delete($key));
        if ($success !== true) {
            throw new SdkException('PsrSimpleCacheStore 删除失败', 0, null, ['key' => $key, 'operation' => 'delete']);
        }
    }

    /**
     * 通过业务注入的锁回调执行互斥逻辑。
     */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        if (!is_callable($this->locker)) {
            throw new SdkException('PsrSimpleCacheStore 未配置锁能力');
        }

        return ($this->locker)($key, max(1, $ttl), $callback);
    }

    private function execute(string $operation, string $key, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (CacheException $exception) {
            throw new SdkException(
                'PsrSimpleCacheStore 后端操作失败',
                0,
                $exception,
                ['key' => $key, 'operation' => $operation],
            );
        }
    }
}
