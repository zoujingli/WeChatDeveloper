# 配置与凭证

所有平台配置实现 `We\Contract\ConfigInterface`，在构造或 `fromArray()` 时立即验证必填字段和密钥。配置属性在校验后保持只读，配置无效时不会创建可调用的客户端。

除 `notification_tolerance_seconds` 明确接受非负整数外，`fromArray()` 的配置字段必须是字符串。数组、对象、布尔值或浮点数不会被隐式转换；类型不匹配时抛出对应平台的 SDK 异常。

## 根客户端

`We\Client` 可注入运行态缓存、微信服务平台授权方 Token 仓库和 Guzzle HTTP 客户端：

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Support\FileCacheStore;

$client = new Client(
    cache: new FileCacheStore(__DIR__ . '/runtime/wechat-cache'),
    cacheKeyPrefix: 'production-tenant-a',
);
```

`cacheKeyPrefix` 必须非空，用于隔离部署或租户。未注入缓存时使用系统临时目录中的文件缓存。

## 微信公众平台

```php
<?php

declare(strict_types=1);

use We\Config\WechatPlatformConfig;

$config = new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
    token: 'callbackToken123',
    encodingAesKey: 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
    storageScope: 'tenant-a',
);
```

数组字段：`appid`、`appsecret`/`app_secret`、`token`、`encodingaeskey`/`encoding_aes_key`、`storage_scope`。

`token` 与 `encodingAesKey` 只有消息签名或安全模式加解密场景需要；`appid` 与 `appSecret` 始终必填。

## 微信小程序

```php
<?php

declare(strict_types=1);

use We\Config\WechatWxappConfig;

$config = WechatWxappConfig::fromArray([
    'appid' => 'wx_appid',
    'app_secret' => 'app_secret',
    'storage_scope' => 'tenant-a',
]);
```

## 微信服务平台

```php
<?php

declare(strict_types=1);

use We\Config\WechatServiceConfig;

$config = new WechatServiceConfig(
    componentAppid: 'wx_component_appid',
    componentAppSecret: 'component_secret',
    componentToken: 'componentToken123',
    componentEncodingAesKey: 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
    storageScope: 'tenant-a',
);
```

数组字段：`component_appid`、`component_appsecret`/`component_app_secret`、`component_token`、`component_encodingaeskey`/`component_encoding_aes_key`、`storage_scope`。

## 微信支付 APIv3

```php
<?php

declare(strict_types=1);

use We\Config\WechatPaymentConfig;

$config = new WechatPaymentConfig(
    appid: 'wx_appid',
    mchId: '1900000001',
    apiV3Key: '0123456789abcdef0123456789abcdef',
    merchantSerial: 'merchant_certificate_serial',
    merchantPrivateKey: $merchantPrivateKeyPem,
    platformPublicKey: $wechatPayPlatformPublicKeyPem,
    platformSerial: 'wechatpay_platform_key_or_certificate_serial',
    notificationToleranceSeconds: 300,
);
```

商户私钥用于请求签名。`platformPublicKey` 或 `platformCertificate` 至少配置一个，并与 `platformSerial` 一起用于普通响应和通知验签。平台公钥优先于平台证书。

数组字段：

| 构造参数 | `fromArray()` 字段 |
|----------|---------------------|
| `appid` | `appid` |
| `mchId` | `mch_id` / `mchid` |
| `apiV3Key` | `api_v3_key` / `mch_v3_key` |
| `merchantSerial` | `merchant_serial` / `cert_serial` |
| `merchantPrivateKey` | `merchant_private_key` / `cert_private` |
| `platformCertificate` | `platform_certificate` |
| `platformPublicKey` | `platform_public_key` |
| `platformSerial` | `platform_serial` |
| `notificationToleranceSeconds` | `notification_tolerance_seconds`，默认 `300` |

旧字段 `cert_public` 是商户证书，不会映射到微信支付平台证书。2.0 必须显式提供平台信任材料。

`notificationToleranceSeconds` 不得小于 0。`fromArray()` 只接受非负整数或仅含数字的字符串，不会把空字符串、布尔值或任意文字转换成 `0`。`0` 表示调用方明确关闭通知时间检查；它不会关闭 RSA 验签。

## 支付宝

```php
<?php

declare(strict_types=1);

use We\Config\AlipayPaymentConfig;

$config = new AlipayPaymentConfig(
    appid: '2026000000000000',
    privateKey: $applicationPrivateKey,
    alipayPublicKey: $alipayPublicKey,
    signType: 'RSA2',
);
```

应用私钥和 `alipayPublicKey` 均为必填项。数组字段是 `appid`/`app_id`、`private_key`/`merchant_private_key`、`alipay_public_key`、`gateway`、`charset`、`sign_type`、`format`、`version`。

`AlipayPlatformConfig` 与 `AlipayPaymentConfig` 使用同一套网关和密钥字段，后者创建支付客户端。

## RSA 规则

- 微信支付商户私钥、微信支付平台公钥/证书、支付宝应用私钥和支付宝公钥必须是 RSA。
- EC 或其他可被 OpenSSL 解析但算法不匹配的密钥会被拒绝。
- 支付宝私钥支持完整 PEM，也支持无头尾的 PKCS#1 或 PKCS#8 Base64 正文。
- 支付宝公钥支持完整 PEM 或无头尾公钥正文。
- 不要把商户证书、公钥和平台公钥混用；它们代表不同信任主体。
