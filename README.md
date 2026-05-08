# WeChatDeveloper

WeChatDeveloper 是一个面向 **微信** 与 **支付宝** 的轻量 PHP SDK，根命名空间为 `We`。

它只维护基础认证、通用 HTTP 调用、签名验签、回调解密和统一异常，不内置业务表结构、不绑定具体框架，也不维护海量接口别名。业务系统按官方文档传入接口 path、参数和配置即可。

## 特性

- 支持微信公众平台、小程序、微信服务平台、微信支付 APIv3。
- 支持支付宝开放平台与支付网关调用。
- 统一入口 `We\Client`，按通道创建客户端。
- 配置对象实现 `ConfigInterface`，构造时完成基础校验。
- 缓存只依赖 `StoreCacheInterface`，可用于单机文件缓存或集群 Redis 适配。
- 微信服务平台授权方 refresh token 通过 `StoreTokenInterface` 由业务系统存取。
- 支持 JSON、原始响应、二进制下载和 multipart 上传等协议层通用能力。
- 返回值默认是数组，失败时抛出 `WechatException` 或其子类。

## 支持边界与域名说明

SDK 支持的是官方协议/API 调用能力，不是对所有网页后台的 100% 自动化封装：

| 域名/平台 | SDK 支持方式 | 边界 |
|-----------|--------------|------|
| `api.weixin.qq.com` | 公众平台、小程序、第三方平台接口，按官方 path 调用。 | 不内置每个接口别名；业务按官方文档传 path 与参数。 |
| `open.weixin.qq.com` | 生成网页授权、扫码登录等跳转 URL。 | 跳转后的用户交互和回调业务由应用处理。 |
| `mp.weixin.qq.com` | 生成第三方平台授权页 URL。 | 不模拟或爬取公众号后台网页。 |
| `api.mch.weixin.qq.com` | 微信支付 APIv3 请求签名、下载、通知验签与解密。 | 不操作 `pay.weixin.qq.com` 商户后台页面。 |
| 支付宝开放平台 | `openapi.alipay.com` 网关签名调用、`openauth.alipay.com` 授权 URL、支付/退款/通知验签。 | 不封装支付宝商家中心网页后台。 |

如果官方新增接口但仍使用这些协议形态（GET/JSON POST/raw/download/multipart/网关表单/签名验签），通常可以直接用 `get()`、`post()`、`call()`、`raw()`、`download()` 或 `upload()` 调用，无需等待 SDK 增加新方法。

## 环境要求

- PHP `>= 8.1`
- `ext-json`
- `ext-openssl`
- `ext-simplexml`
- `guzzlehttp/guzzle`
- `psr/simple-cache`

## 安装

稳定版发布后：

```bash
composer require zoujingli/wechat-developer:^2.0
```

开发版：

```bash
composer require zoujingli/wechat-developer:2.0.x-dev
```

源码开发：

```bash
cd WeChatDeveloper
composer install
composer validate --strict
composer test
```

## 项目结构

```text
src/
├── Client.php              # SDK 根入口与通道客户端工厂
├── Config/                 # 微信、支付宝平台配置对象
├── Contract/               # 配置、缓存、授权方 Token 存储契约
│   └── Trait/              # 微信 JSON、raw/download/upload、Token 注入等协议层复用能力
├── Exception/              # SDK 异常类型
├── Platform/
│   ├── Wechat/             # 微信公众平台、小程序、微信服务平台、微信支付 APIv3 客户端
│   └── Alipay/             # 支付宝开放平台与支付客户端
└── Support/                # 缓存、HTTP、签名、XML、消息/支付解密工具

tests/                      # PHPUnit 测试用例，命名空间 We\Tests
```

测试入口为根目录 `tests/`，`phpunit.xml` 使用 `tests/bootstrap.php` 引导 Composer autoload。

## 快速开始

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$client = new Client();

$platform = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$users = $platform->get('cgi-bin/user/get', [
    'next_openid' => '',
]);
```

`post()`、`get()`、`call()` 的 path 与官方文档保持一致，通常不需要前导 `/`。

```php
$menu = $platform->post('cgi-bin/menu/create', [
    'button' => [
        [
            'type' => 'click',
            'name' => '今日推荐',
            'key' => 'TODAY',
        ],
    ],
]);
```


## 调用约定

SDK 不把官方接口包装成大量固定方法，核心约定是“官方文档 path + 参数数组”：

```php
// GET：第二个参数会作为 query string。
$result = $platform->get('cgi-bin/user/get', ['next_openid' => '']);

// POST：第二个参数默认作为 JSON body。
$result = $platform->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);

// call：显式指定 HTTP 方法，并可透传 Guzzle options。
$result = $platform->call('cgi-bin/menu/get', [], 'GET');
```

调用时需要注意：

| 场景 | 写法 |
|------|------|
| 接口 path | 只传相对路径，如 `cgi-bin/user/get`；SDK 会拒绝 `https://...` 或 `//...`。 |
| 微信普通接口需要 `access_token` | 默认自动附加。 |
| 微信授权、登录等不需要 `access_token` 的接口 | 传 `['with_token' => false]`。 |
| POST JSON | 默认行为，直接传 `$params`。 |
| GET query | 用 `get($path, $query)`。 |
| 自定义 query + JSON body | `post($path, $body, ['query' => [...]])`；也可显式传 `json`。 |
| 表单提交或原始 body | 透传 Guzzle 的 `form_params` 或 `body`。 |
| 二进制/非 JSON 响应 | 用 `raw()` 或 `download()` 返回 PSR-7 Response。 |
| multipart 上传 | 用 `upload($path, $multipart, $query)`，SDK 会按通道规则附加 token。 |
| 微信支付敏感字段加密 | 如需 `Wechatpay-Serial`，手动在 `headers` 传微信支付平台证书/公钥序列号。 |

示例：小程序登录接口不需要 access token，应该关闭自动 token：

```php
$session = $wxapp->get('sns/jscode2session', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'js_code' => 'login_code',
    'grant_type' => 'authorization_code',
], ['with_token' => false]);
```

图片、媒体、文件等非 JSON 响应可直接获取原始响应：

```php
$response = $platform->download('cgi-bin/media/get', [
    'media_id' => 'MEDIA_ID',
]);

$binary = (string) $response->getBody();
```

上传媒体或文件时传入 Guzzle multipart 结构：

```php
$media = $platform->upload('cgi-bin/media/upload', [
    [
        'name' => 'media',
        'contents' => fopen(__DIR__ . '/demo.jpg', 'rb'),
        'filename' => 'demo.jpg',
    ],
], [
    'type' => 'image',
]);
```

### 协议层能力说明

微信公众平台、小程序、微信服务平台共用 `WechatInteractsProtocol` 协议层能力：

| 方法 | 说明 |
|------|------|
| `request()` | 发送 JSON 风格接口请求并解析为数组。 |
| `raw()` | 返回 PSR-7 Response，不解析 JSON，适合图片、媒体、二维码等原始响应。 |
| `download()` | 以 GET 方式获取二进制资源，并按通道规则附加 token 或签名。 |
| `upload()` | 透传 Guzzle `multipart` 上传结构，并解析平台 JSON 响应。 |

微信支付 APIv3 的 `raw()` 与 `download()` 会先按官方规则生成 `Authorization` 签名，再返回原始响应。支付宝网关请求统一复用公共参数构造、签名和验签逻辑，电脑网站支付跳转地址也使用同一套签名参数。

### 接口类型示例速查

下面示例展示不同协议形态的写法，接口参数仍以官方文档为准。

**1. 微信 GET query**

```php
$users = $platform->get('cgi-bin/user/get', [
    'next_openid' => '',
]);
```

**2. 微信 POST JSON**

```php
$message = $platform->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);
```

**3. 微信无 token 接口（登录、网页授权换 token 等）**

```php
$session = $wxapp->get('sns/jscode2session', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'js_code' => $code,
    'grant_type' => 'authorization_code',
], ['with_token' => false]);

$oauth = $platform->get('sns/oauth2/access_token', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'code' => $code,
    'grant_type' => 'authorization_code',
], ['with_token' => false]);
```

**4. 自定义 query + JSON body / 表单 / 原始 body**

```php
// query + JSON body；未显式传 json 时，$body 会作为 JSON 请求体。
$result = $platform->post('cgi-bin/draft/add', $body, [
    'query' => ['debug' => '1'],
]);

// 表单提交。
$result = $platform->call('cgi-bin/example/form', [], 'POST', [
    'form_params' => ['name' => 'value'],
]);

// 原始 body，适合少数非 JSON 协议接口。
$result = $platform->call('cgi-bin/example/raw', [], 'POST', [
    'body' => $rawBody,
    'headers' => ['Content-Type' => 'text/plain'],
]);
```

**5. 原始响应、下载、上传**

```php
$response = $platform->raw('POST', 'cgi-bin/qrcode/create', [], [
    'json' => ['expire_seconds' => 60, 'action_name' => 'QR_STR_SCENE'],
]);

$image = $platform->download('cgi-bin/media/get', [
    'media_id' => 'MEDIA_ID',
]);

$upload = $platform->upload('cgi-bin/media/upload', [
    ['name' => 'media', 'contents' => fopen(__DIR__ . '/demo.jpg', 'rb'), 'filename' => 'demo.jpg'],
], [
    'type' => 'image',
]);
```

**6. 微信消息安全模式解密/加密**

```php
$plain = $platform->post('decrypt_message', [
    'body' => $rawXml,
    'msg_signature' => $_GET['msg_signature'] ?? '',
    'timestamp' => $_GET['timestamp'] ?? '',
    'nonce' => $_GET['nonce'] ?? '',
]);

$encrypted = $platform->post('encrypt_message', [
    'body' => $replyXml,
    'timestamp' => (string) time(),
    'nonce' => $nonce,
]);

echo $encrypted['xml'];
```

**7. 微信第三方平台：组件 token、授权 URL、代授权方调用**

```php
$componentToken = $service->componentAccessToken($componentVerifyTicket);
$preAuth = $service->createPreAuthCode($componentToken);
$authUrl = $service->authorizationUrl((string) $preAuth['pre_auth_code'], $redirectUri);
$auth = $service->queryAuth($componentToken, $authorizationCode);

$menu = $service->get('cgi-bin/menu/get', [], [
    'authorizer_appid' => 'authorizer_appid',
    'component_access_token' => $componentToken,
]);
```

**8. 微信支付 APIv3：GET/POST/download/通知解密**

```php
$order = $payment->post('v3/pay/transactions/jsapi', [
    'appid' => 'wx_appid',
    'mchid' => '1900000001',
    'description' => '测试订单',
    'out_trade_no' => 'T202605080001',
    'notify_url' => 'https://example.com/wechat-pay/notify',
    'amount' => ['total' => 1, 'currency' => 'CNY'],
    'payer' => ['openid' => 'openid'],
]);

$query = $payment->get('v3/pay/transactions/out-trade-no/T202605080001', [
    'mchid' => '1900000001',
]);

$bill = $payment->download('v3/bill/tradebill', [
    'bill_date' => '2026-05-08',
]);

// 如果接口涉及敏感信息加密，Wechatpay-Serial 应传微信支付平台证书/公钥序列号，不是商户证书序列号。
$result = $payment->post('v3/example/with-sensitive-info', $payload, [
    'headers' => ['Wechatpay-Serial' => 'wechatpay_platform_serial'],
]);

$decrypted = $payment->post('decrypt_notification', [], [
    'headers' => $headers,
    'raw_body' => $rawBody,
]);
```

**9. 支付宝：授权 URL、网关调用、支付、退款、通知验签、数据解密**

```php
$authUrl = $alipay->get('auth', [
    'redirect_uri' => 'https://example.com/alipay/callback',
    'scope' => 'auth_user',
    'state' => 'STATE',
])['url'];

$user = $alipay->post('alipay.user.info.share', [], [
    'auth_token' => $authToken,
]);

$page = $alipayPayment->post('page', [
    'out_trade_no' => 'P202605080001',
    'total_amount' => '0.01',
    'subject' => '测试订单',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
], [
    'return_url' => 'https://example.com/alipay/return',
    'notify_url' => 'https://example.com/alipay/notify',
])['url'];

$refund = $alipayPayment->post('refund', [
    'out_trade_no' => 'P202605080001',
    'refund_amount' => '0.01',
]);

$ok = $alipayPayment->verifyNotify($_POST);

$plain = $alipay->post('decrypt', [
    'encrypted_data' => $encryptedData,
    'session_key' => $sessionKey,
    'iv' => $iv,
]);
```

## 配置来源示例

生产项目通常从数据库或配置中心读取账号配置，再使用 `fromArray()` 构造配置对象：

```php
use We\Client;
use We\Config\WechatPlatformConfig;
use We\Support\FileCacheStore;

$row = [
    'appid' => 'wx_appid',
    'appsecret' => 'app_secret',
    'token' => 'messageToken123',
    'encoding_aes_key' => 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
    'storage_scope' => 'tenant:10001:account:20002',
];

$client = new Client(
    cache: new FileCacheStore(__DIR__ . '/runtime/wechat-cache'),
    cacheKeyPrefix: 'my_project_prod',
);

$platform = $client->wechatPlatform(WechatPlatformConfig::fromArray($row));
```

`cacheKeyPrefix` 建议按项目和环境区分，例如 `mall_prod`、`mall_test`。`storage_scope` 建议按租户、账号或业务线区分，避免同一 appid 在不同业务上下文中复用缓存。

## 入口 Client

```php
use GuzzleHttp\ClientInterface;
use We\Client;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;

new Client(
    ?StoreCacheInterface $cache = null,
    ?StoreTokenInterface $authorizers = null,
    ?ClientInterface $http = null,
    string $cacheKeyPrefix = Client::DEFAULT_CACHE_KEY_PREFIX,
);
```

参数说明：

| 参数 | 说明 |
|------|------|
| `$cache` | SDK 运行态缓存，主要保存 access token；不传时默认使用 `FileCacheStore`。 |
| `$authorizers` | 微信服务平台代调用时，读取和回写授权方 refresh token。 |
| `$http` | 可注入自定义 Guzzle HTTP 客户端，便于统一超时、代理或单元测试。 |
| `$cacheKeyPrefix` | 缓存键前缀，默认 `wechat_developer`；生产环境建议按项目设置。 |

也可以用字符串通道创建客户端：

```php
$platform = $client->get('wechat.platform', WechatPlatformConfig::fromArray($config));
```

支持的通道：

| 通道 | 工厂方法 | 配置对象 |
|------|----------|----------|
| `wechat.platform` | `wechatPlatform()` | `WechatPlatformConfig` |
| `wechat.wxapp` | `wechatWxapp()` | `WechatWxappConfig` |
| `wechat.service` | `wechatService()` | `WechatServiceConfig` |
| `wechat.payment` | `wechatPayment()` | `WechatPaymentConfig` |
| `alipay.platform` | `alipayPlatform()` | `AlipayPlatformConfig` |
| `alipay.payment` | `alipayPayment()` | `AlipayPaymentConfig` |

### 初始化模式

默认初始化适合单机开发或简单部署：

```php
use We\Client;

$client = new Client(); // 默认 FileCacheStore + 官方默认 HTTP 客户端
```

生产环境建议显式配置缓存前缀和共享缓存：

```php
use We\Client;
use We\Support\FileCacheStore;

$client = new Client(
    cache: new FileCacheStore(__DIR__ . '/runtime/wechat-cache'),
    cacheKeyPrefix: 'mall_prod',
);
```

如果要注入自定义 Guzzle 客户端，需注意微信类客户端使用相对 path，请为对应通道配置正确 `base_uri`；多通道使用不同域名时，建议分别创建根 `Client`：

```php
use GuzzleHttp\Client as GuzzleClient;
use We\Client;

$wechatClient = new Client(
    http: new GuzzleClient([
        'base_uri' => 'https://api.weixin.qq.com/',
        'timeout' => 10.0,
        // 需要代理或中间件时可在这里继续配置 Guzzle options。
    ]),
);

$paymentClient = new Client(
    http: new GuzzleClient([
        'base_uri' => 'https://api.mch.weixin.qq.com/',
        'timeout' => 10.0,
    ]),
);
```

支付宝网关请求使用配置中的绝对 `gateway`，自定义 HTTP 客户端主要用于统一超时、代理、日志中间件或测试替身。

## 配置对象

所有配置对象都实现 `ConfigInterface`：

```php
interface ConfigInterface
{
    public static function fromArray(array $data): static;

    public function validate(): void;
}
```

构造函数会调用 `validate()`。缺少必填字段时会抛出 `WechatException`。

示例：

```php
use We\Config\WechatWxappConfig;

$config = WechatWxappConfig::fromArray([
    'appid' => 'wx_appid',
    'appsecret' => 'app_secret',
    'storage_scope' => 'tenant:1',
]);
```

`storage_scope` 是可选的缓存分桶标识。同一个 appid 在多租户或多业务账号下需要隔离 token 时可以设置。

### fromArray 完整配置示例

字段名兼容常见下划线写法，便于直接接数据库或配置中心：

```php
use We\Config\AlipayPaymentConfig;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Config\WechatWxappConfig;

$wechatPlatform = WechatPlatformConfig::fromArray([
    'appid' => 'wx_appid',
    'appsecret' => 'app_secret',
    'token' => 'messageToken123',
    'encoding_aes_key' => 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
    'storage_scope' => 'tenant:1:official',
]);

$wechatWxapp = WechatWxappConfig::fromArray([
    'appid' => 'wx_appid',
    'appsecret' => 'app_secret',
    'storage_scope' => 'tenant:1:wxapp',
]);

$wechatService = WechatServiceConfig::fromArray([
    'component_appid' => 'wx_component_appid',
    'component_appsecret' => 'component_secret',
    'component_token' => 'componentToken123',
    'component_encoding_aes_key' => 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
    'storage_scope' => 'tenant:1:component',
]);

$wechatPayment = WechatPaymentConfig::fromArray([
    'appid' => 'wx_appid',
    'mch_id' => '1900000001',
    'api_v3_key' => str_repeat('k', 32),
    'merchant_serial' => 'merchant_cert_serial',
    'merchant_private_key' => file_get_contents(__DIR__ . '/apiclient_key.pem'),
    // 二选一：平台证书 PEM 或平台公钥 PEM；平台序列号用于回调头校验。
    'platform_certificate' => file_get_contents(__DIR__ . '/wechatpay_cert.pem'),
    'platform_public_key' => '',
    'platform_serial' => 'wechatpay_platform_serial',
]);

$alipayPlatform = AlipayPlatformConfig::fromArray([
    'app_id' => '2021000000000000',
    'private_key' => file_get_contents(__DIR__ . '/merchant_private_key.pem'),
    'alipay_public_key' => file_get_contents(__DIR__ . '/alipay_public_key.pem'),
    'gateway' => 'https://openapi.alipay.com/gateway.do',
    'sign_type' => 'RSA2',
]);

$alipayPayment = AlipayPaymentConfig::fromArray([
    'app_id' => '2021000000000000',
    'private_key' => file_get_contents(__DIR__ . '/merchant_private_key.pem'),
    'alipay_public_key' => file_get_contents(__DIR__ . '/alipay_public_key.pem'),
]);
```

构造时会做基础格式校验：

| 项 | 校验 |
|----|------|
| 微信消息 Token / componentToken | 3-32 位英文或数字。 |
| EncodingAESKey | 43 位字符串，Base64 解码后必须是 32 字节。 |
| 微信支付 APIv3 Key | 必须是 32 字节字符串。 |
| RSA 私钥、公钥、证书 | 必须能被 OpenSSL 解析；支付宝支持无头尾的密钥正文。 |
| 支付宝 gateway/sign_type | gateway 必须是有效 URL，sign_type 仅支持 `RSA` / `RSA2`。 |

## 缓存与锁

access token 统一通过 `StoreCacheInterface` 存储：

```php
interface StoreCacheInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, int $ttl): void;

    public function del(string $key): void;

    public function lock(string $key, int $ttl, callable $callback): mixed;
}
```

内置实现：

| 类 | 说明 |
|----|------|
| `We\Support\FileCacheStore` | 默认单机文件缓存，使用 JSON 文件保存数据，刷新锁使用 `flock`。 |
| `We\Support\PsrSimpleCacheStore` | PSR-16 缓存适配器，适合 Redis、Memcached 或框架缓存。锁能力需由构造参数注入。 |
| `We\Support\NullCacheStore` | 空缓存，适合测试或明确不缓存的场景。 |

单机文件缓存：

```php
use We\Client;
use We\Support\FileCacheStore;

$client = new Client(
    cache: new FileCacheStore(__DIR__ . '/runtime/wechat-cache'),
    cacheKeyPrefix: 'my_app',
);
```

PSR-16 缓存：

```php
use Psr\SimpleCache\CacheInterface;
use We\Client;
use We\Support\PsrSimpleCacheStore;

/** @var CacheInterface $cache */
$client = new Client(
    cache: new PsrSimpleCacheStore(
        cache: $cache,
        locker: static function (string $key, int $ttl, callable $callback): mixed {
            // 在这里接入 Redis SET NX、框架锁或其他原子锁。
            return $callback();
        },
    ),
);
```

如果使用 `PsrSimpleCacheStore` 且未配置锁回调，调用 `lock()` 会抛出明确异常。集群部署时必须使用共享缓存和可用的原子锁，避免并发刷新 access token。

缓存键固定为：

```text
{cacheKeyPrefix}:{channel}:{logicalKey}
```

例如：

```text
my_app:wechat.platform:wechat:app:wx123:platform:access_token
```

完整键由 `CacheKey` 生成，微信 token 逻辑键由 `TokenCacheKey` 生成。

## 微信服务平台授权方 Token

微信服务平台代调用授权方接口时，SDK 需要读取授权方 refresh token，并在刷新后把新 token 回写业务存储。业务系统实现 `StoreTokenInterface` 即可：

```php
use We\Contract\StoreTokenInterface;

final class AuthorizerTokenStore implements StoreTokenInterface
{
    public function refreshToken(string $authorizerAppid): string
    {
        // 从数据库读取 authorizer_refresh_token。
        return 'authorizer_refresh_token';
    }

    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void
    {
        // 保存 authorizer_access_token、authorizer_refresh_token、expires_in 等平台返回数据。
    }
}
```

注入到根 Client：

```php
$client = new Client(
    cache: $cache,
    authorizers: new AuthorizerTokenStore(),
);
```

## 微信公众平台

```php
use We\Config\WechatPlatformConfig;

$platform = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
    token: 'messageToken123',
    encodingAesKey: 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
));

$accessToken = $platform->accessToken();

$result = $platform->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);
```

安全模式消息解密：

```php
$plain = $platform->post('decrypt_message', [
    'body' => $rawBody,
    'msg_signature' => $signature,
    'timestamp' => $timestamp,
    'nonce' => $nonce,
]);
```

## 微信小程序

登录换取 `openid` 与 `session_key`：

```php
use We\Config\WechatWxappConfig;

$wxapp = $client->wechatWxapp(new WechatWxappConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$session = $wxapp->get('sns/jscode2session', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'js_code' => 'login_code',
    'grant_type' => 'authorization_code',
], ['with_token' => false]);
```

获取手机号：

```php
$phone = $wxapp->post('wxa/business/getuserphonenumber', [
    'code' => 'phone_code_from_client',
]);
```

如果官方接口返回图片、文件等二进制内容，使用 `download()` 或 `raw()` 获取原始 PSR-7 Response；默认 `post()` 仍按 JSON 响应解析。

## 微信服务平台

```php
use We\Config\WechatServiceConfig;

$componentAppid = 'component_appid';
$service = $client->wechatService(new WechatServiceConfig(
    componentAppid: $componentAppid,
    componentAppSecret: 'component_secret',
    componentToken: 'componentToken123',
    componentEncodingAesKey: 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
));

$componentToken = $service->componentAccessToken($componentVerifyTicket);
$preAuth = $service->createPreAuthCode($componentToken);
$url = $service->authorizationUrl((string) $preAuth['pre_auth_code'], $redirectUri);
```

代授权方调用：

```php
$result = $service->get('cgi-bin/menu/get', [], [
    'authorizer_appid' => 'authorizer_appid',
    'component_access_token' => $componentToken,
]);
```

## 微信支付 APIv3

```php
use We\Config\WechatPaymentConfig;

$payment = $client->wechatPayment(new WechatPaymentConfig(
    appid: 'wx_appid',
    mchId: 'mch_id',
    apiV3Key: str_repeat('k', 32),
    merchantSerial: 'merchant_serial',
    merchantPrivateKey: file_get_contents(__DIR__ . '/apiclient_key.pem'),
    platformPublicKey: file_get_contents(__DIR__ . '/wechatpay_public.pem'),
    platformSerial: 'platform_serial',
));

$refund = $payment->post('v3/refund/domestic/refunds', [
    'out_trade_no' => 'T202605020001',
    'out_refund_no' => 'R202605020001',
    'amount' => [
        'refund' => 1,
        'total' => 1,
        'currency' => 'CNY',
    ],
]);
```

下载账单等非 JSON 响应时使用已签名的 `download()`：

```php
$bill = $payment->download('v3/bill/tradebill', [
    'bill_date' => '2026-05-08',
]);
```

回调验签与解密：

```php
$rawBody = file_get_contents('php://input') ?: '';
$body = json_decode($rawBody, true) ?: [];

$data = $payment->post('decrypt_notification', [], [
    'headers' => $headers,
    // 微信支付 APIv3 验签必须使用原始 JSON body，不要先 json_decode 后重新编码。
    'raw_body' => $rawBody,
    'body' => $body,
]);
```

## 支付宝

```php
use We\Config\AlipayPlatformConfig;

$alipay = $client->alipayPlatform(new AlipayPlatformConfig(
    appid: 'app_id',
    privateKey: file_get_contents(__DIR__ . '/merchant_private_key.pem'),
    alipayPublicKey: file_get_contents(__DIR__ . '/alipay_public_key.pem'),
));

$auth = $alipay->get('auth', [
    'redirect_uri' => 'https://example.com/callback',
    'scope' => 'auth_user',
    'state' => 'state',
]);

$response = $alipay->post('alipay.user.info.share', [], [
    'auth_token' => 'auth_token',
]);
```

支付宝支付：

```php
use We\Config\AlipayPaymentConfig;

$payment = $client->alipayPayment(AlipayPaymentConfig::fromArray([
    'appid' => 'app_id',
    'private_key' => file_get_contents(__DIR__ . '/merchant_private_key.pem'),
    'alipay_public_key' => file_get_contents(__DIR__ . '/alipay_public_key.pem'),
]));

$page = $payment->post('page', [
    'out_trade_no' => 'P202605020001',
    'total_amount' => '0.01',
    'subject' => 'Test Order',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
]);
```


## 常见业务案例

### 微信公众平台：创建菜单并发送客服消息

```php
$platform->post('cgi-bin/menu/create', [
    'button' => [
        [
            'name' => '服务',
            'sub_button' => [
                ['type' => 'view', 'name' => '官网', 'url' => 'https://example.com'],
                ['type' => 'click', 'name' => '帮助', 'key' => 'HELP'],
            ],
        ],
    ],
]);

$platform->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => '您好，客服消息已发送。'],
]);
```

### 微信公众平台：网页授权 URL 与用户资料

```php
$redirectUri = 'https://example.com/oauth/callback';
$authUrl = $platform->get('connect/oauth2/authorize', [
    'redirect_uri' => $redirectUri,
    'scope' => 'snsapi_userinfo',
    'state' => 'state-value',
]);
$url = $authUrl['url'];

$oauth = $platform->get('sns/oauth2/access_token', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'code' => $code,
    'grant_type' => 'authorization_code',
], ['with_token' => false]);

$user = $platform->get('sns/userinfo', [
    'access_token' => $oauth['access_token'],
    'openid' => $oauth['openid'],
    'lang' => 'zh_CN',
], ['with_token' => false]);
```

### 微信服务平台：授权回调后保存授权方 Token

```php
$componentToken = $service->componentAccessToken($componentVerifyTicket);
$auth = $service->queryAuth($componentToken, $authorizationCode);

$authorization = $auth['authorization_info'] ?? [];
$authorizerAppid = (string)($authorization['authorizer_appid'] ?? '');

// 业务系统应把 authorizer_refresh_token 保存到数据库，后续 StoreTokenInterface 会读取它。
$repository->saveAuthorizerToken($authorizerAppid, $authorization);
```

### 微信支付：创建 JSAPI 订单

```php
$order = $payment->post('v3/pay/transactions/jsapi', [
    'appid' => 'wx_appid',
    'mchid' => 'mch_id',
    'description' => '测试订单',
    'out_trade_no' => 'T202605020001',
    'notify_url' => 'https://example.com/wechat-payment/notify',
    'amount' => ['total' => 1, 'currency' => 'CNY'],
    'payer' => ['openid' => 'openid'],
]);
```

前端调起支付需要的 `paySign` 可由业务系统使用返回的 `prepay_id` 再按微信支付文档签名生成。SDK 只负责 APIv3 请求签名、回调验签与资源解密。

### 支付宝：电脑网站支付与退款

```php
$page = $payment->post('page', [
    'out_trade_no' => 'P202605020001',
    'total_amount' => '0.01',
    'subject' => '测试订单',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
], [
    'return_url' => 'https://example.com/alipay/return',
    'notify_url' => 'https://example.com/alipay/notify',
]);

$refund = $payment->post('refund', [
    'out_trade_no' => 'P202605020001',
    'refund_amount' => '0.01',
    'refund_reason' => '用户退款',
]);
```

支付宝异步通知验签：

```php
if (!$payment->verifyNotify($_POST)) {
    throw new RuntimeException('支付宝通知验签失败');
}

// 验签通过后再处理 trade_status、out_trade_no、trade_no 等业务字段。
```

## 框架集成建议

- 在 Laravel、Hyperf、Symfony 等框架中，建议把 `Client` 注册为容器服务，缓存实现接入框架 Redis 或 Cache 组件。
- 多租户系统应把租户 ID、账号 ID 放入 `storage_scope` 或 `cacheKeyPrefix`，保证 token 缓存隔离。
- 密钥、证书、APIv3 Key、支付宝私钥应由业务系统加密保存，运行时解密后传入配置对象。
- 日志中不要记录 app secret、access token、refresh token、私钥、证书、回调密文和支付签名。

## 异常处理

SDK 抛出的异常基类为：

```php
We\Exception\WechatException
```

签名相关异常使用：

```php
We\Exception\SignatureException
```

建议在业务边界统一捕获并转换成应用自己的响应格式。不要把 app secret、access token、private key、回调密文等敏感内容写入日志。

## 测试

```bash
cd WeChatDeveloper
composer cs:check
composer analyse
composer test
```

或在项目根目录：

```bash
vendor/bin/phpunit -c phpunit.xml
```

## 设计边界

WeChatDeveloper 只处理协议层和 HTTP 编排：

- 不提供账号、租户、菜单草稿、订单、授权记录等业务表。
- 不托管密钥加密存储。
- 不绑定 Hyperf、Laravel、Symfony 等框架。
- 不保证覆盖每一个官方接口别名；SDK 通过官方 path、JSON、raw/download、multipart upload 等协议层能力支持接口调用。

这种边界使 SDK 更适合作为开源底层包，被后台系统、SaaS 平台或命令行工具组合使用。

## License

MIT
