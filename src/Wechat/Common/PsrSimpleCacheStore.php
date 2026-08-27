<?php

declare(strict_types=1);

namespace We\Wechat\Common;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use We\Common\Exception\SdkException;

/** 将缓存委托给 PSR-16 实现，并通过调用方回调提供刷新锁。 */
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
        return $this->execute('get', $key, fn (): mixed => $this->cache->get($key, $default));
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $success = $this->execute('set', $key, fn (): bool => $this->cache->set($key, $value, max(1, $ttl)));
        if ($success !== true) {
            throw new SdkException('PSR-16 缓存写入失败', 0, null, ['key' => $key, 'operation' => 'set']);
        }
    }

    public function del(string $key): void
    {
        $success = $this->execute('delete', $key, fn (): bool => $this->cache->delete($key));
        if ($success !== true) {
            throw new SdkException('PSR-16 缓存删除失败', 0, null, ['key' => $key, 'operation' => 'delete']);
        }
    }

    /** 通过调用方注入的锁回调执行互斥逻辑。 */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        if (!is_callable($this->locker)) {
            throw new SdkException('PSR-16 缓存未配置锁能力');
        }

        return ($this->locker)($key, max(1, $ttl), $callback);
    }

    private function execute(string $operation, string $key, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (CacheException $exception) {
            throw new SdkException(
                'PSR-16 缓存后端操作失败',
                0,
                $exception,
                ['key' => $key, 'operation' => $operation],
            );
        }
    }
}
