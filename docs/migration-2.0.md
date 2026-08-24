# 从 1.x 迁移到 2.0

WeChatDeveloper 2.0 是主版本重写，不提供兼容层。升级不是替换版本号即可完成：需要更新 PHP 运行时、命名空间、初始化、凭证、缓存、异常和每个业务调用。

建议先在独立分支列出实际使用的 1.x 类和方法，再按官方 API 协议逐项迁移并运行集成测试。不要在同一进程混用两套入口。

## 破坏性变化总览

| 主题 | 1.x | 2.0 |
|------|-----|-----|
| PHP | `>=5.4` | PHP 8.1 或更高 |
| 自动加载 | `WeChat\`、`WeMini\`、`WePay\`、`WePayV3\`、`AliPay\` 和 `We.php` | 单一 `We\` PSR-4 命名空间 |
| 初始化 | 各业务类 `::instance(array $config)` | `We\Client` + 配置对象 + 类型工厂 |
| API 表面 | 大量业务类和别名方法 | 官方相对 path + `get()`/`post()`/`call()` |
| HTTP | 内置 curl/tools | Guzzle 7，可注入 `ClientInterface` |
| 配置 | 松散数组、回调和文件路径 | 构造时验证的 `ConfigInterface` 对象 |
| 缓存 | `cache_path`、Tools 静态文件缓存、token callback | `StoreCacheInterface`、TTL 和刷新锁 |
| 异常 | `WeChat\Exceptions\*` | `SdkException` 统一基类和平台子类 |
| 返回 | 业务类各自返回数组/XML/字符串 | JSON API 通常返回数组，raw/download 返回 PSR-7 Response |
| 微信支付 | V2 XML/MD5/HMAC 类与独立 V3 类 | 只提供微信支付 APIv3 通用客户端 |
| 支付信任 | 部分响应未强制验签 | 支付宝和微信支付响应默认强制验签 |

## 安装和命名空间

更新运行环境与依赖：

```bash
composer require zoujingli/wechat-developer:^2.0
```

移除旧代码中的 `WeChat\`、`WeMini\`、`WePay\`、`WePayV3\` 和 `AliPay\` imports。2.0 的入口统一为 `We\Client`。

## 初始化

1.x：

```php
<?php

declare(strict_types=1);

use WeChat\User;

$user = User::instance([
    'appid' => 'wx_appid',
    'appsecret' => 'app_secret',
]);
```

2.0：

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$platform = (new Client())->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));
```

不再使用静态 `instance()` 缓存客户端。应用容器可把 `Client` 或平台客户端注册为服务，并显式注入缓存和 HTTP 适配器。

## 常用类映射

| 1.x 类族 | 2.0 入口 | 迁移方式 |
|----------|----------|----------|
| `WeChat\User`、`Menu`、`Media`、`Template` 等 | `Client::wechatPlatform()` | 查官方公众号 path，使用 `get()`/`post()`/`download()`/`upload()` |
| `WeMini\*` | `Client::wechatWxapp()` | 查官方小程序 path，按是否需要 token 调用 |
| 第三方平台自定义接入 | `Client::wechatService()` | 使用 component token、授权 URL 和授权方调用能力 |
| `WePay\Order`、`Refund`、`Bill` 等 V2 类 | `Client::wechatPayment()` | 迁移到官方 APIv3 path、JSON 字段和 RSA 凭证；不是一对一方法替换 |
| `WePayV3\*` | `Client::wechatPayment()` | 将旧 V3 path 和 payload 迁到通用 `get()`/`post()` |
| `AliPay\Trade`、`Web`、`Wap`、`Transfer` 等 | `Client::alipayPayment()` | 使用官方 method、`page()`、`refund()` 或通用网关请求 |
| 支付宝授权/用户接口 | `Client::alipayPlatform()` | 使用 `auth()` 或官方 API method |

## 调用映射示例

1.x 的 `WeChat\User::getUserList()`：

```php
<?php

declare(strict_types=1);

$users = $platform->get('cgi-bin/user/get', [
    'next_openid' => '',
]);
```

1.x 的 `WeChat\User::updateMark()`：

```php
<?php

declare(strict_types=1);

$result = $platform->post('cgi-bin/user/info/updateremark', [
    'openid' => 'openid',
    'remark' => '新备注',
]);
```

1.x 支付宝 `Trade::query()`：

```php
<?php

declare(strict_types=1);

$trade = $alipayPayment->post('alipay.trade.query', [
    'out_trade_no' => 'A202608100001',
]);
```

1.x 微信支付 V2 XML 接口不能只改 path。应先在微信支付官方文档选择对应 APIv3 接口，再迁移金额单位、字段名、通知格式、签名和证书/公钥配置。

## 配置字段

### 微信公众平台和小程序

- `appid` 保持不变。
- `appsecret` 数组字段仍可由 `fromArray()` 读取；构造参数名为 `appSecret`。
- 配置数组中的凭证、标识和 URL 字段必须是字符串，不再把数组、布尔值或对象隐式转换为字符串。
- `cache_path` 被移除，改为向根 `Client` 注入缓存。
- `GetAccessTokenCallback` 被移除。普通 access token 由 SDK 通过缓存管理；授权方 token 使用 `StoreTokenInterface`。

### 微信支付

- `mch_id` -> `mchId`。
- `mch_v3_key`/`api_v3_key` -> `apiV3Key`，必须正好 32 字节。
- `cert_serial`/`merchant_serial` -> `merchantSerial`。
- `cert_private`/`merchant_private_key` -> `merchantPrivateKey`，必须是 RSA 私钥。
- 新增必填平台信任材料：`platform_public_key` 或 `platform_certificate`。
- 新增必填 `platform_serial`，必须与收到的 `Wechatpay-Serial` 对应。
- 新增 `notification_tolerance_seconds`，默认 300；`0` 才显式关闭通知时间检查。

旧 `cert_public` 表示商户证书/公钥，不能验证微信支付平台响应。2.0 不会把 `cert_public` 映射为 `platform_certificate`；必须从微信支付平台取得正确的 `platform_public_key` 或平台证书及其序列号。

### 支付宝

- `appid`/`app_id` -> `appid`。
- `private_key`/`merchant_private_key` -> `privateKey`。
- `alipay_public_key` 现在必填，用于同步响应和通知验签。
- RSA2 仍是默认签名类型。
- 无头尾 PKCS#1 与 PKCS#8 RSA 私钥正文可继续使用；EC 密钥会被拒绝。

## 缓存迁移

实现 `StoreCacheInterface`，或把现有 PSR-16 缓存包装为 `PsrSimpleCacheStore`。生产实现必须提供跨进程/跨节点刷新锁，不能只实现 `get()` 与 `set()`。

2.0 缓存键使用三段 PSR-16 安全格式，段内点号编码为 `%2E`。不要复用 1.x 缓存文件名或手工拼接旧 key；让 SDK 首次调用时重新获取 token。

## 异常迁移

将 `WeChat\Exceptions\InvalidArgumentException`、`InvalidResponseException` 和 `LocalCacheException` 等 catch 迁移到新层级：

```php
<?php

declare(strict_types=1);

use We\Exception\AlipayApiException;
use We\Exception\AlipaySignatureException;
use We\Exception\ApiException;
use We\Exception\SdkException;
use We\Exception\SignatureException;
use We\Exception\TransportException;

try {
    $result = $platform->get('cgi-bin/user/get');
} catch (SignatureException $exception) {
    security_log($exception);
} catch (AlipaySignatureException $exception) {
    security_log($exception);
} catch (ApiException | AlipayApiException $exception) {
    platform_log($exception, $exception->context());
} catch (TransportException $exception) {
    retryable_log($exception, $exception->context());
} catch (SdkException $exception) {
    sdk_log($exception);
}
```

`SdkException` 适合统一边界；`TransportException`、平台 API 异常和平台签名异常适合需要重试、告警或拒绝策略的代码。

## 返回结构

- JSON API 成功返回 `array<string,mixed>`，不再返回 1.x `DataArray`。
- `raw()` 与 `download()` 返回 PSR-7 `ResponseInterface`；通过 `(string) $response->getBody()` 获取内容。
- 微信账单使用 `downloadBill()`，不是对申请账单 path 调用普通 `download()`。
- 平台错误通过异常上下文提供原始字段，不要依赖 1.x 错误数组形状。

## 已移除能力

2.0 不提供兼容层，也不包含：

- 1.x 的 `We.php` 全局入口与 helper 自动加载。
- 旧命名空间和各业务类的 `::instance()`。
- `WeChat\*`、`WeMini\*`、`WePay\*`、`WePayV3\*`、`AliPay\*` 的大量别名方法。
- 微信支付 V2 XML/MD5/HMAC 业务封装。
- `cache_path` 静态文件缓存配置和 access token callback。
- SDK 内置的订单、退款、红包、分账等业务对象。

替代方式是使用 2.0 平台客户端按官方 path 调用；若某个旧功能依赖已废弃平台协议，应先迁移到平台当前协议，而不是在 2.0 中复制旧实现。

## 升级检查清单

1. 将运行环境升级到 PHP 8.1+，安装新 Composer 依赖和扩展。
2. 清点并移除所有 1.x 命名空间、静态 `instance()` 和 helper 调用。
3. 为每个平台建立配置对象，补齐支付宝公钥和微信支付平台信任材料。
4. 把缓存接入 `StoreCacheInterface`，验证生产刷新锁。
5. 按官方 path 替换业务类方法，单独处理微信支付 V2 到 APIv3 的协议迁移。
6. 更新异常捕获和 raw/download 返回处理。
7. 使用原始 body 验证支付通知，并在业务数据库实现幂等。
8. 跑完应用单元测试、集成测试和支付沙箱/受控环境验证后再切换流量。
