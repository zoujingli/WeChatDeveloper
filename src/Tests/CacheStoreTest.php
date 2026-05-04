<?php

declare(strict_types=1);

namespace We\Tests;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use We\Exception\WechatException;
use We\Support\FileCacheStore;
use We\Support\NullCacheStore;
use We\Support\PsrSimpleCacheStore;

#[CoversClass(FileCacheStore::class)]
#[CoversClass(NullCacheStore::class)]
#[CoversClass(PsrSimpleCacheStore::class)]
final class CacheStoreTest extends TestCase
{
    public function testFileCacheStoreSetGetDelAndTtl(): void
    {
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);

        $this->assertSame('missing', $store->get('k1', 'missing'));
        $store->set('k1', 'value', 3600);
        $this->assertSame('value', $store->get('k1'));
        $store->del('k1');
        $this->assertSame('missing', $store->get('k1', 'missing'));

        $store->set('k2', 'expired', -10);
        sleep(2);
        $this->assertSame('missing', $store->get('k2', 'missing'));

        $this->removeDir($dir);
    }

    public function testFileCacheStoreLockExecutesCallback(): void
    {
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);

        $result = $store->lock('lock:k1', 10, static fn (): string => 'locked');

        $this->assertSame('locked', $result);
        $this->removeDir($dir);
    }

    public function testNullCacheStoreLockExecutesCallback(): void
    {
        $store = new NullCacheStore();

        $this->assertSame('ok', $store->lock('k', 10, static fn (): string => 'ok'));
    }

    public function testPsrSimpleCacheStoreThrowsWhenLockerMissing(): void
    {
        $store = new PsrSimpleCacheStore(new ArraySimpleCache());

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('锁能力');

        $store->lock('k', 10, static fn (): string => 'never');
    }

    public function testPsrSimpleCacheStoreUsesInjectedLocker(): void
    {
        $calls = [];
        $store = new PsrSimpleCacheStore(new ArraySimpleCache(), static function (string $key, int $ttl, callable $callback) use (&$calls): mixed {
            $calls[] = [$key, $ttl];

            return $callback();
        });

        $this->assertSame('ok', $store->lock('k', 10, static fn (): string => 'ok'));
        $this->assertSame([['k', 10]], $calls);
    }

    private function tempDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wechatdev_cache_' . bin2hex(random_bytes(8));
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            $file->isDir() ? @rmdir($path) : @unlink($path);
        }
        @rmdir($dir);
    }
}

final class ArraySimpleCache implements CacheInterface
{
    /** @var array<string,mixed> */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get((string)$key, $default);
        }
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string)$key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string)$key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}
