# 缓存

SDK 使用 `We\Contract\StoreCacheInterface` 缓存 access token 和 component access token。接口语义包括 TTL、删除和刷新锁：

```php
<?php

declare(strict_types=1);

use We\Contract\StoreCacheInterface;

final class ApplicationCache implements StoreCacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $default;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
    }

    public function del(string $key): void
    {
    }

    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        return $callback();
    }
}
```

生产适配器的 `lock()` 必须在多进程或多节点之间互斥执行回调，避免多个请求同时刷新同一平台凭据。上例只展示接口形状，不具备生产锁语义。

## 内置实现

| 实现 | 用途 | 锁语义 |
|------|------|--------|
| `FileCacheStore` | 单机、本地开发、小规模部署 | `flock` 进程锁 |
| `PsrSimpleCacheStore` | Redis 等 PSR-16 实现 | 由调用方注入分布式锁回调 |
| `NullCacheStore` | 测试或显式禁用缓存 | 直接执行回调，不互斥 |

文件缓存使用临时文件和原子重命名提交新值。读取过期文件只返回默认值，不会按旧路径删除，因此不会误删并发写入的新值。

## PSR-16 适配

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Support\PsrSimpleCacheStore;

$store = new PsrSimpleCacheStore(
    cache: $psr16Cache,
    locker: static function (string $key, int $ttl, callable $callback) use ($distributedLock): mixed {
        return $distributedLock->run($key, $ttl, $callback);
    },
);

$client = new Client(
    cache: $store,
    cacheKeyPrefix: 'production-tenant-a',
);
```

如果没有注入 `locker`，需要刷新 token 时 `PsrSimpleCacheStore::lock()` 会抛出 `SdkException`，而不是静默绕过并发保护。

## 缓存键

完整键由部署前缀、平台通道和逻辑键三段组成。每段使用 `rawurlencode()`，再用点号连接：

```text
production-tenant-a.wechat.platform.wechat%3Aapp%3Awx_appid%3Aplatform%3Aaccess_token
```

该格式不会包含 PSR-16 保留字符 `{ } ( ) / \\ @ :`。部署前缀和通道仍保持稳定隔离；逻辑键的内部层次被编码在第三段内。

2.0 发布前的旧冒号格式不属于稳定接口。升级时不需要迁移旧 token 缓存，允许 SDK 按新键重新获取。

## 多租户

- 使用根 `Client::$cacheKeyPrefix` 隔离部署或大租户。
- 使用微信配置中的 `storageScope` 隔离同一 appid 下的业务账号或子租户。
- 不要在请求间随机改变这两个值，否则会失去缓存命中并放大平台 token 请求。
