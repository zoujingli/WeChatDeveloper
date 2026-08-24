# 公开 API 速查

SDK 的主调用面是根客户端、六个平台客户端和三个扩展契约。业务代码优先从 `We\Client` 创建客户端；`We\Support` 中的实现用于适配协议细节，不应代替平台客户端成为业务入口。

## 根客户端

`We\Client` 接受可选的缓存、服务平台授权方 Token 仓库、Guzzle HTTP 客户端和缓存键前缀：

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$client = new Client(
    cache: $cacheStore,
    authorizers: $authorizerTokenStore,
    http: $httpClient,
    cacheKeyPrefix: 'production-tenant-a',
);

$platform = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));
```

六个显式类型工厂如下：

| 工厂 | 配置对象 | 返回客户端 |
| --- | --- | --- |
| `wechatPlatform()` | `WechatPlatformConfig` | `We\Platform\Wechat\PlatformClient` |
| `wechatWxapp()` | `WechatWxappConfig` | `We\Platform\Wechat\WxappClient` |
| `wechatService()` | `WechatServiceConfig` | `We\Platform\Wechat\ServiceClient` |
| `wechatPayment()` | `WechatPaymentConfig` | `We\Platform\Wechat\PaymentClient` |
| `alipayPlatform()` | `AlipayPlatformConfig` | `We\Platform\Alipay\PlatformClient` |
| `alipayPayment()` | `AlipayPaymentConfig` | `We\Platform\Alipay\PaymentClient` |

配置驱动场景使用 `Client::get($channel, $config)`。支持的通道是 `wechat.platform`、`wechat.wxapp`、`wechat.service`、`wechat.payment`、`alipay.platform` 和 `alipay.payment`；配置类型与通道不匹配时抛出 `SdkException`。所有配置属性在构造校验后保持只读。

未注入缓存时，`Client` 使用 `Client::defaultCacheStoreDirectory()` 返回的系统临时目录子目录创建 `FileCacheStore`。生产环境应显式注入符合部署拓扑的缓存实现。

## 通用调用语义

| 客户端 | 方法 | 行为 |
| --- | --- | --- |
| 微信公众平台、小程序 | `get()` / `post()` | 使用官方相对 path；GET 参数进入 query，POST 参数默认进入 JSON body；默认附加 access token |
| 微信公众平台、小程序 | `request()` | 显式指定 HTTP 方法、query、Guzzle options 和是否附加 token |
| 微信公众平台、小程序 | `raw()` / `download()` | 返回 PSR-7 `ResponseInterface`，用于非 JSON 数据 |
| 微信公众平台、小程序 | `upload()` | 接受 Guzzle multipart 数组并解析平台 JSON 响应 |
| 微信服务平台 | `get()` / `post()` / `call()` | 调用第三方平台接口；同时提供 `authorizer_appid` 与 `component_access_token` options 时可代授权方调用 |
| 微信服务平台 | `request()` | 直接调用第三方平台接口，不解析授权方控制项；代授权方调用使用 `requestAsAuthorizer()` |
| 微信支付 | `get()` / `post()` / `request()` | 对商户请求签名，验证平台响应签名后解析 JSON |
| 微信支付 | `raw()` / `download()` | 对请求签名并返回原始响应；响应 body 未被解释为可信业务 JSON |
| 支付宝开放平台 | `request()` | 使用官方 API method 和业务参数调用网关，验签后返回响应节点 |
| 支付宝客户端 | `get()` / `post()` / `call()` | 兼容统一客户端调用形态；支付宝网关传输仍使用官方 POST 表单协议 |

微信通用客户端只接受相对 path。微信支付账单的绝对下载地址只能通过 `downloadBill()` 使用，不能传给通用调用入口。

## 微信公众平台与小程序专用能力

- `accessToken($refresh = false)`：读取缓存 token；传 `true` 强制刷新。
- `call('connect/oauth2/authorize', ...)`：生成公众号网页授权 URL，返回 `['url' => string]`。
- `call('connect/qrconnect', ...)`：生成网站应用扫码登录 URL，返回 `['url' => string]`。
- `post('decrypt_message', ...)` / `post('encrypt_message', ...)`：处理公众平台消息安全模式。
- `options['with_token'] = false`：调用无需公众号或小程序 access token 的接口。

完整示例见[微信平台](wechat.md)。

## 微信服务平台专用能力

| 方法 | 用途 |
| --- | --- |
| `componentAccessToken()` | 获取并缓存第三方平台 `component_access_token` |
| `createPreAuthCode()` | 创建授权流程所需的 `pre_auth_code` |
| `authorizationUrl()` | 生成第三方平台授权页地址 |
| `queryAuth()` | 使用 `authorization_code` 查询授权信息 |
| `authorizerInfo()` | 获取授权方账号基本信息 |
| `requestAsAuthorizer()` | 使用授权方 access token 调用公众号或小程序接口 |

授权方 refresh token 的读取和刷新结果回写由 `StoreTokenInterface` 承担。完整流程见[微信平台](wechat.md#微信服务平台)。

## 支付专用能力

- 微信支付 `downloadBill()`：先验签账单地址响应，再限制为 HTTPS 且禁止重定向地下载文件。
- 微信支付 `post('decrypt_notification', ...)`：使用原始 body 验签、检查时间窗口并解密通知 resource。
- 支付宝 `auth()`：生成网页授权地址。
- 支付宝 `decrypt()`：校验 16 字节 key/IV，解密 Base64 编码的 AES-128-CBC 数据并解析 JSON。
- 支付宝支付 `page()`：生成电脑网站支付跳转 URL。
- 支付宝支付 `refund()`：调用 `alipay.trade.refund`。
- 支付宝 `verifyNotify()`：验证异步通知参数签名，不代替金额、商户身份和幂等校验。

支付调用必须同时遵循[配置与凭证](configuration.md)、[微信支付](payments.md)或[支付宝](alipay.md)中的信任材料和验签要求。

## 扩展契约与适配器

| 类型 | 接入目的 |
| --- | --- |
| `ConfigInterface` | 统一配置对象的 `fromArray()` 和 `validate()` 行为 |
| `StoreCacheInterface` | 提供 token 的 TTL、删除和刷新互斥 |
| `StoreTokenInterface` | 读取服务平台授权方 refresh token，并保存刷新后的完整 token payload |
| `FileCacheStore` | 单机文件缓存和进程锁 |
| `PsrSimpleCacheStore` | 把 PSR-16 缓存和业务提供的分布式锁适配为 SDK 缓存契约 |
| `NullCacheStore` | 测试或明确禁用缓存，不提供互斥能力 |

缓存实现要求见[缓存](cache.md)，配置字段要求见[配置与凭证](configuration.md)。所有 SDK 故障类型和捕获策略见[异常](exceptions.md)。
