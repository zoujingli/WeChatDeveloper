<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use We\Exception\SdkException;
use We\Support\CacheKey;
use We\Support\FileCacheStore;
use We\Support\NullCacheStore;
use We\Support\PsrSimpleCacheStore;
use We\Support\TokenCacheKey;

/**
 * SDK 缓存存储实现测试用例。
 * @internal
 */
#[CoversClass(FileCacheStore::class)]
#[CoversClass(NullCacheStore::class)]
#[CoversClass(PsrSimpleCacheStore::class)]
final class CacheStoreTest extends TestCase
{
    /**
     * 测试文件缓存的写入、读取、删除与过期行为。
     */
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

    /**
     * 测试文件缓存锁会执行回调。
     */
    public function testFileCacheStoreLockExecutesCallback(): void
    {
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);

        $result = $store->lock('lock:k1', 10, static fn (): string => 'locked');

        $this->assertSame('locked', $result);
        $this->removeDir($dir);
    }

    public function testFileCacheStoreWrapsJsonEncodingFailureAsSdkException(): void
    {
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);
        $recursive = [];
        $recursive['self'] = &$recursive;

        try {
            $store->set('recursive', $recursive, 60);
            self::fail('Expected the recursive value to be rejected.');
        } catch (SdkException $exception) {
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        } finally {
            $this->removeDir($dir);
        }
    }

    public function testFileCacheStoreRestrictsNewCacheFilePermissions(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('POSIX file permission bits are not available on Windows.');
        }
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);
        $store->set('sensitive-token', 'token-value', 60);
        $files = iterator_to_array(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        ));
        $cacheFile = array_values(array_filter(
            $files,
            static fn (\SplFileInfo $file): bool => $file->isFile() && str_ends_with($file->getFilename(), '.json'),
        ))[0] ?? null;

        self::assertInstanceOf(\SplFileInfo::class, $cacheFile);
        self::assertSame(0600, $cacheFile->getPerms() & 0777);
        self::assertSame(0700, fileperms($dir) & 0777);
        $this->removeDir($dir);
    }

    public function testExpiredReadCannotDeleteConcurrentFreshWrite(): void
    {
        if (!function_exists('pcntl_fork') || !function_exists('stream_socket_pair')) {
            self::markTestSkipped('This concurrency regression requires pcntl and local sockets.');
        }
        $dir = $this->tempDir();
        $store = new FileCacheStore($dir);
        $store->set('race-key', str_repeat('x', 16 * 1024 * 1024), 1);
        sleep(2);
        $files = iterator_to_array(new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        ));
        $cacheFile = array_values(array_filter(
            $files,
            static fn (\SplFileInfo $file): bool => $file->isFile() && str_ends_with($file->getFilename(), '.json'),
        ))[0] ?? null;
        self::assertInstanceOf(\SplFileInfo::class, $cacheFile);
        $lock = fopen($cacheFile->getPathname(), 'rb');
        self::assertIsResource($lock);
        self::assertTrue(flock($lock, LOCK_EX));
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        self::assertIsArray($sockets);

        $pid = pcntl_fork();
        self::assertGreaterThanOrEqual(0, $pid);
        if ($pid === 0) {
            fclose($sockets[0]);
            fwrite($sockets[1], 'ready');
            $store->get('race-key', 'expired');
            fclose($sockets[1]);
            exit(0);
        }

        fclose($sockets[1]);
        self::assertSame('ready', fread($sockets[0], 5));
        flock($lock, LOCK_UN);
        usleep(1000);
        self::assertTrue(flock($lock, LOCK_EX));
        $store->set('race-key', 'fresh', 3600);
        flock($lock, LOCK_UN);
        fclose($lock);
        fclose($sockets[0]);
        pcntl_waitpid($pid, $status);

        self::assertSame(0, pcntl_wexitstatus($status));
        self::assertSame('fresh', $store->get('race-key', 'missing'));
        $this->removeDir($dir);
    }

    /**
     * 测试空缓存锁会直接执行回调。
     */
    public function testNullCacheStoreLockExecutesCallback(): void
    {
        $store = new NullCacheStore();

        $this->assertSame('ok', $store->lock('k', 10, static fn (): string => 'ok'));
    }

    /**
     * 测试 PSR 缓存未配置锁能力时抛出异常。
     */
    public function testPsrSimpleCacheStoreThrowsWhenLockerMissing(): void
    {
        $store = new PsrSimpleCacheStore(new ArraySimpleCache());

        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('锁能力');

        $store->lock('k', 10, static fn (): string => 'never');
    }

    /**
     * 测试 PSR 缓存会使用注入的锁回调。
     */
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

    public function testPsrSimpleCacheStoreAcceptsGeneratedTokenKey(): void
    {
        $cache = new ArraySimpleCache();
        $store = new PsrSimpleCacheStore($cache);
        $key = CacheKey::compose(
            'tenant@example',
            'wechat.platform',
            TokenCacheKey::wechatPlatformAccessToken('wx/app:1'),
        );

        $store->set($key, 'token', 3600);

        self::assertSame('token', $store->get($key));
        self::assertDoesNotMatchRegularExpression('/[{}()\/\\\@:]/', $key);
    }

    public function testPsrSimpleCacheStoreWrapsBackendFailure(): void
    {
        $failure = new TestCacheException('backend unavailable');
        $store = new PsrSimpleCacheStore(new ArraySimpleCache(failure: $failure));

        try {
            $store->get('token');
            self::fail('Expected the backend failure to be wrapped.');
        } catch (SdkException $exception) {
            self::assertSame($failure, $exception->getPrevious());
            self::assertSame('get', $exception->context()['operation']);
        }
    }

    public function testPsrSimpleCacheStoreDoesNotMaskProgrammingErrors(): void
    {
        $store = new PsrSimpleCacheStore(new ArraySimpleCache(
            failure: new \TypeError('backend programming error'),
        ));

        $this->expectException(\TypeError::class);

        $store->get('token');
    }

    public function testPsrSimpleCacheStoreRejectsUnsuccessfulWrite(): void
    {
        $store = new PsrSimpleCacheStore(new ArraySimpleCache(writeResult: false));

        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('写入失败');

        $store->set('token', 'value', 60);
    }

    public function testPsrSimpleCacheStoreRejectsUnsuccessfulDelete(): void
    {
        $store = new PsrSimpleCacheStore(new ArraySimpleCache(deleteResult: false));

        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('删除失败');

        $store->del('token');
    }

    /**
     * 创建本次测试使用的临时目录路径。
     */
    private function tempDir(): string
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'wechatdev_cache_' . bin2hex(random_bytes(8));
    }

    /**
     * 递归删除测试临时目录。
     */
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

/**
 * 测试用 PSR-16 内存缓存实现。
 */
final class ArraySimpleCache implements CacheInterface
{
    /** @var array<string,mixed> */
    private array $values = [];

    public function __construct(
        private readonly ?\Throwable $failure = null,
        private readonly bool $writeResult = true,
        private readonly bool $deleteResult = true,
    ) {}

    /**
     * 读取测试缓存值。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->failWhenConfigured();
        $this->assertValidKey($key);

        return $this->values[$key] ?? $default;
    }

    /**
     * 写入缓存值。
     */
    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->failWhenConfigured();
        $this->assertValidKey($key);
        if (!$this->writeResult) {
            return false;
        }
        $this->values[$key] = $value;

        return true;
    }

    /**
     * 删除测试缓存值。
     */
    public function delete(string $key): bool
    {
        $this->failWhenConfigured();
        $this->assertValidKey($key);
        if (!$this->deleteResult) {
            return false;
        }
        unset($this->values[$key]);

        return true;
    }

    /**
     * 清空测试缓存。
     */
    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    /**
     * 批量读取测试缓存值。
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get((string)$key, $default);
        }
    }

    /**
     * 批量写入测试缓存值。
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string)$key, $value, $ttl);
        }

        return true;
    }

    /**
     * 批量删除测试缓存值。
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string)$key);
        }

        return true;
    }

    /**
     * 判断测试缓存键是否存在。
     */
    public function has(string $key): bool
    {
        $this->assertValidKey($key);

        return array_key_exists($key, $this->values);
    }

    private function assertValidKey(string $key): void
    {
        if ($key === '' || preg_match('/[{}()\/\\\@:]/', $key) === 1) {
            throw new \InvalidArgumentException('Invalid PSR-16 key: ' . $key);
        }
    }

    private function failWhenConfigured(): void
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
    }
}

final class TestCacheException extends \RuntimeException implements CacheException {}
