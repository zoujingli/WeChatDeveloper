<?php

declare(strict_types=1);

namespace We\Support;

use We\Contract\StoreCacheInterface;
use We\Exception\SdkException;

/**
 * 本地文件缓存实现：缓存值以 JSON 保存，并使用 flock 提供单机多进程刷新锁。
 */
final class FileCacheStore implements StoreCacheInterface
{
    /**
     * 创建文件缓存目录并校验可写权限。
     */
    public function __construct(private readonly string $directory)
    {
        if ($this->directory === '') {
            throw new SdkException('FileCacheStore 目录不能为空');
        }
        if (!is_dir($this->directory) && @mkdir($this->directory, 0775, true) !== true) {
            throw new SdkException('FileCacheStore 无法创建目录: ' . $this->directory);
        }
        if (!is_writable($this->directory)) {
            throw new SdkException('FileCacheStore 目录不可写: ' . $this->directory);
        }
    }

    /**
     * 读取文件缓存值；文件不存在、过期或 JSON 无效时返回默认值。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return $default;
        }
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return $default;
        }

        try {
            if (!flock($handle, LOCK_SH)) {
                return $default;
            }
            $raw = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if (!is_string($raw) || $raw === '') {
            return $default;
        }

        /** @var null|array{expires_at?:int,value?:mixed} $payload */
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !array_key_exists('expires_at', $payload) || !array_key_exists('value', $payload)) {
            return $default;
        }
        if ((int)$payload['expires_at'] <= time()) {
            return $default;
        }

        return $payload['value'];
    }

    /**
     * 以原子写入方式保存缓存值与过期时间。
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        $path = $this->pathFor($key);
        $dir = dirname($path);
        if (!is_dir($dir) && @mkdir($dir, 0775, true) !== true) {
            throw new SdkException('FileCacheStore 无法创建子目录: ' . $dir);
        }

        try {
            $body = json_encode(
                ['expires_at' => time() + max(1, $ttl), 'value' => $value],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
        } catch (\JsonException $exception) {
            throw new SdkException(
                'FileCacheStore 缓存值无法 JSON 编码',
                0,
                $exception,
                ['key' => $key],
            );
        }
        $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $body, LOCK_EX) === false) {
            @unlink($tmp);
            throw new SdkException('FileCacheStore 写入失败: ' . $tmp);
        }
        if (!@rename($tmp, $path)) {
            @unlink($path);
            if (!@rename($tmp, $path)) {
                @unlink($tmp);
                throw new SdkException('FileCacheStore 提交失败: ' . $path);
            }
        }
    }

    /**
     * 删除指定缓存键对应的文件。
     */
    public function del(string $key): void
    {
        $path = $this->pathFor($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * 基于 flock 在互斥锁内执行回调。
     */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        $path = $this->pathFor('lock:' . $key) . '.lock';
        $dir = dirname($path);
        if (!is_dir($dir) && @mkdir($dir, 0775, true) !== true) {
            throw new SdkException('FileCacheStore 无法创建锁目录: ' . $dir);
        }

        $handle = @fopen($path, 'c');
        if ($handle === false) {
            throw new SdkException('FileCacheStore 无法创建锁文件: ' . $path);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new SdkException('FileCacheStore 获取锁失败: ' . $key);
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * 根据缓存键生成哈希分片后的文件路径。
     */
    private function pathFor(string $key): string
    {
        $hash = hash('sha256', $key);

        return $this->directory . DIRECTORY_SEPARATOR . substr($hash, 0, 2) . DIRECTORY_SEPARATOR . $hash . '.json';
    }
}
