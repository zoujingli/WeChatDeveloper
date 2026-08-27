# 缓存

SDK 使用 `We\Wechat\Common\StoreCacheInterface` 缓存微信 access Token、component Token 和 authorizer Token。接口语义包括 TTL、删除和刷新锁：

```php
<?php

declare(strict_types=1);

use We\Wechat\Common\StoreCacheInterface;

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

Token 响应的 `expires_in` 必须是大于 0 的数值。SDK 按 `max(1, expires_in - 300)` 秒写入缓存，并使用 30 秒刷新锁；锁内会再次读取缓存，避免等待期间重复刷新。

微信 Token HTTP 调用使用 20 秒超时和 1 MiB 响应上限。HTTP 状态、JSON、平台错误、Token 字段或有效期任一校验失败时都不会写入缓存。缓存适配器无法读取、写入、删除或获取刷新锁时必须抛出 `We\Common\Exception\SdkException`；锁内回调异常按原类型向上传递。

## 内置实现

| 实现 | 用途 | 锁语义 |
|------|------|--------|
| `We\Wechat\Common\FileCacheStore` | 单机、本地开发、小规模部署 | `flock` 进程锁 |
| `We\Wechat\Common\PsrSimpleCacheStore` | Redis 等 PSR-16 实现 | 由调用方注入分布式锁回调 |
| `We\Wechat\Common\NullCacheStore` | 测试或显式禁用缓存 | 直接执行回调，不互斥 |

文件缓存先写入临时文件，再优先通过原子重命名提交新值；不支持覆盖重命名的平台会删除旧文件后再次重命名。读取过期文件只返回默认值，不会按旧路径删除，因此不会误删并发写入的新值。

通道未传入 `Runtime` 时，会在系统临时目录的 SDK 专用子目录中创建 `FileCacheStore`。该默认值适合本地开发和单机运行；容器、多实例或无持久临时目录的生产部署应显式注入共享缓存与匹配的锁实现。

新建文件缓存目录使用 POSIX `0700` 权限，缓存值文件在发布前收紧为 `0600`。Windows 不使用 POSIX 权限位；部署侧仍应保证缓存目录只对运行 SDK 的账号开放。

## PSR-16 适配

```php
<?php

declare(strict_types=1);

use We\Common\Runtime;
use We\WeChatClient;
use We\Wechat\Common\PsrSimpleCacheStore;

$store = new PsrSimpleCacheStore(
    cache: $psr16Cache,
    locker: static function (string $key, int $ttl, callable $callback) use ($distributedLock): mixed {
        return $distributedLock->run($key, $ttl, $callback);
    },
);

$runtime = new Runtime(
    cache: $store,
    cacheKeyPrefix: 'production-tenant-a',
);
$client = WeChatClient::mk($config, $runtime);
```

如果没有注入 `locker`，需要刷新 Token 时 `PsrSimpleCacheStore::lock()` 会抛出 `We\Common\Exception\SdkException`，而不是静默绕过并发保护。

## 缓存键

完整键由部署前缀、平台通道和逻辑键三段组成。每段先使用 `rawurlencode()`，再把不会被该函数编码的点号替换为 `%2E`，最后用点号连接：

```text
production-tenant-a.wechat%2Eplatform.wechat%3Aapp%3Awx_appid%3Aplatform%3Aaccess_token
```

该格式不会包含 PSR-16 保留字符 `{ } ( ) / \\ @ :`，也不会让段内容中的点号与分隔符发生碰撞。部署前缀和通道仍保持稳定隔离；逻辑键的内部层次被编码在第三段内。

2.0 发布前的旧冒号格式不属于稳定接口。升级时不需要迁移旧 Token 缓存，允许 SDK 按新键重新获取。

## 多租户

- 使用 `Runtime` 的 `cacheKeyPrefix` 隔离部署或大租户。
- 使用微信配置中的 `storageScope` 隔离同一 appid 下的业务账号或子租户。
- 不要在请求间随机改变这两个值，否则会失去缓存命中并放大平台 Token 请求。
