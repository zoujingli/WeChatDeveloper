# WeChatDeveloper

WeChatDeveloper 是一个面向 **微信** 与 **支付宝** 的轻量 PHP SDK，根命名空间为 `We`。

它只维护基础认证、通用 HTTP 调用、签名验签、回调解密和统一异常，不内置业务表结构、不绑定具体框架，也不维护海量接口别名。业务系统按官方文档传入接口 path、参数和配置即可。

## 特性

- 支持微信公众号、小程序、微信开放平台、微信支付 APIv3。
- 支持支付宝开放平台与支付网关调用。
- 统一入口 `We\Client`，按通道创建客户端。
- 配置对象实现 `ConfigInterface`，构造时完成基础校验。
- 缓存只依赖 `StoreCacheInterface`，可用于单机文件缓存或集群 Redis 适配。
- 开放平台授权方 refresh token 通过 `StoreTokenInterface` 由业务系统存取。
- 返回值默认是数组，失败时抛出 `WechatException` 或其子类。

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
composer test
```

## 快速开始

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$client = new Client();

$official = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$users = $official->get('cgi-bin/user/get', [
    'next_openid' => '',
]);
```

`post()`、`get()`、`call()` 的 path 与官方文档保持一致，通常不需要前导 `/`。

```php
$menu = $official->post('cgi-bin/menu/create', [
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
$result = $official->get('cgi-bin/user/get', ['next_openid' => '']);

// POST：第二个参数默认作为 JSON body。
$result = $official->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);

// call：显式指定 HTTP 方法，并可透传 Guzzle options。
$result = $official->call('cgi-bin/menu/get', [], 'GET');
```

调用时需要注意：

| 场景 | 写法 |
|------|------|
| 微信普通接口需要 `access_token` | 默认自动附加。 |
| 微信授权、登录等不需要 `access_token` 的接口 | 传 `['with_token' => false]`。 |
| POST JSON | 默认行为，直接传 `$params`。 |
| GET query | 用 `get($path, $query)`。 |
| 自定义 query + JSON body | `post($path, $body, ['query' => [...], 'json' => $body])`。 |
| 表单提交或原始 body | 透传 Guzzle 的 `form_params` 或 `body`。 |

示例：小程序登录接口不需要 access token，应该关闭自动 token：

```php
$session = $wxapp->get('sns/jscode2session', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'js_code' => 'login_code',
    'grant_type' => 'authorization_code',
], ['with_token' => false]);
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
    'token' => 'message_token',
    'encoding_aes_key' => 'encoding_aes_key',
    'storage_scope' => 'tenant:10001:account:20002',
];

$client = new Client(
    cache: new FileCacheStore(__DIR__ . '/runtime/wechat-cache'),
    cacheKeyPrefix: 'my_project_prod',
);

$official = $client->wechatPlatform(WechatPlatformConfig::fromArray($row));
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
| `$authorizers` | 微信开放平台代调用时，读取和回写授权方 refresh token。 |
| `$http` | 可注入自定义 Guzzle HTTP 客户端，便于统一超时、代理或单元测试。 |
| `$cacheKeyPrefix` | 缓存键前缀，默认 `wechat_developer`；生产环境建议按项目设置。 |

也可以用字符串通道创建客户端：

```php
$official = $client->get('wechat.platform', WechatPlatformConfig::fromArray($config));
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
my_app:wechat.platform:wechat:app:wx123:official:access_token
```

完整键由 `CacheKey` 生成，微信 token 逻辑键由 `TokenCacheKey` 生成。

## 微信开放平台授权方 Token

开放平台代调用授权方接口时，SDK 需要读取授权方 refresh token，并在刷新后把新 token 回写业务存储。业务系统实现 `StoreTokenInterface` 即可：

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

## 微信公众号

```php
use We\Config\WechatPlatformConfig;

$official = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
    token: 'message_token',
    encodingAesKey: 'encoding_aes_key',
));

$accessToken = $official->accessToken();

$result = $official->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);
```

安全模式消息解密：

```php
$plain = $official->post('decrypt_message', [
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

如果官方接口返回图片、文件等二进制内容，默认 `post()` 会按 JSON 响应解析，不适合直接处理；建议注入自定义 Guzzle 客户端或在业务侧扩展专用下载方法。

## 微信开放平台

```php
use We\Config\WechatServiceConfig;

$service = $client->wechatService(new WechatServiceConfig(
    componentAppid: 'component_appid',
    componentAppSecret: 'component_secret',
    componentToken: 'component_token',
    componentEncodingAesKey: 'component_encoding_aes_key',
));

$componentToken = $service->componentAccessToken($componentVerifyTicket);
$preAuth = $service->createPreAuthCode($componentToken);
$url = $service->authorizationUrl((string) $preAuth['pre_auth_code'], $redirectUri);
```

代授权方调用：

```php
$result = $service->post('cgi-bin/menu/get', [], [
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
    apiV3Key: 'api_v3_key',
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

回调验签与解密：

```php
$data = $payment->post('decrypt_notification', [], [
    'headers' => $headers,
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

$pay = $client->alipayPayment(AlipayPaymentConfig::fromArray([
    'appid' => 'app_id',
    'private_key' => file_get_contents(__DIR__ . '/merchant_private_key.pem'),
    'alipay_public_key' => file_get_contents(__DIR__ . '/alipay_public_key.pem'),
]));

$page = $pay->post('page', [
    'out_trade_no' => 'P202605020001',
    'total_amount' => '0.01',
    'subject' => 'Test Order',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
]);
```


## 常见业务案例

### 公众号：创建菜单并发送客服消息

```php
$official->post('cgi-bin/menu/create', [
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

$official->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => '您好，客服消息已发送。'],
]);
```

### 公众号：网页授权 URL 与用户资料

```php
$redirectUri = 'https://example.com/oauth/callback';
$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query([
    'appid' => 'wx_appid',
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'snsapi_userinfo',
    'state' => 'state-value',
]) . '#wechat_redirect';

$oauth = $official->get('sns/oauth2/access_token', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'code' => $code,
    'grant_type' => 'authorization_code',
], ['with_token' => false]);

$user = $official->get('sns/userinfo', [
    'access_token' => $oauth['access_token'],
    'openid' => $oauth['openid'],
    'lang' => 'zh_CN',
], ['with_token' => false]);
```

### 开放平台：授权回调后保存授权方 Token

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
    'notify_url' => 'https://example.com/wechat-pay/notify',
    'amount' => ['total' => 1, 'currency' => 'CNY'],
    'payer' => ['openid' => 'openid'],
]);
```

前端调起支付需要的 `paySign` 可由业务系统使用返回的 `prepay_id` 再按微信支付文档签名生成。SDK 只负责 APIv3 请求签名、回调验签与资源解密。

### 支付宝：电脑网站支付与退款

```php
$page = $pay->post('page', [
    'out_trade_no' => 'P202605020001',
    'total_amount' => '0.01',
    'subject' => '测试订单',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
], [
    'return_url' => 'https://example.com/alipay/return',
    'notify_url' => 'https://example.com/alipay/notify',
]);

$refund = $pay->post('refund', [
    'out_trade_no' => 'P202605020001',
    'refund_amount' => '0.01',
    'refund_reason' => '用户退款',
]);
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
- 不保证覆盖每一个官方接口别名，通用接口通过官方 path 调用。

这种边界使 SDK 更适合作为开源底层包，被后台系统、SaaS 平台或命令行工具组合使用。

## License

MIT
