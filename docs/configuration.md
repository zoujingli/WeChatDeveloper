# 配置

每个通道 Client 直接绑定一个具体配置类，不经过公共配置接口或运行时类型分派。构造函数和 `fromArray()` 都会校验通道字段；`fromArray()` 还会构造并校验内置密钥 Provider。无效配置抛出 `We\Common\Exception\ConfigurationException`。

| 场景 | 通道 | 配置类型 | 构造参数 | 默认端点 |
| --- | --- | --- | --- | --- |
| 微信公众号 | `wechat.platform` | `We\Wechat\WeChatConfig` | `appid`、`appSecret`、`storageScope`、`tokenStrategy`、`endpoint` | `https://api.weixin.qq.com` |
| 微信小程序 | `wechat.wxapp` | `We\Wechat\WxAppConfig` | `appid`、`appSecret`、`storageScope`、`tokenStrategy`、`endpoint` | `https://api.weixin.qq.com` |
| 微信开放平台 | `wechat.service` | `We\Wechat\WxOpenConfig` | `componentAppid`、`componentAppSecret`、`storageScope`、`endpoint` | `https://api.weixin.qq.com` |
| 微信支付 | `wechat.payment` | `We\Wechat\WxPayConfig` | `appid`、`mchId`、`merchantSigner`、`platformTrust`、`endpoint` | `https://api.mch.weixin.qq.com` |
| 支付宝支付 v2 | `alipay.gateway` | `We\Alipay\AliPayConfig` | `appid`、`signer`、`trust`、`defaultTrustKeyId`、`charset`、`signType`、`format`、`version`、`appCertificateSerial`、`alipayRootCertificateSerial`、`endpoint` | `https://openapi.alipay.com/gateway.do` |
| 支付宝 REST v3 | `alipay.rest` | `We\Alipay\AliRestConfig` | `appid`、`signer`、`trust`、`defaultTrustKeyId`、`appCertificateSerial`、`endpoint` | `https://openapi.alipay.com` |

## 微信公众号与小程序

```php
<?php

declare(strict_types=1);

use We\Wechat\Common\WeChatTokenStrategy;
use We\Wechat\WeChatConfig;
use We\Wechat\WxAppConfig;

$weChat = new WeChatConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
    storageScope: 'tenant-a',
    tokenStrategy: WeChatTokenStrategy::Stable,
);

$wxApp = new WxAppConfig('wx_appid', 'app_secret');
```

## 微信开放平台

```php
<?php

declare(strict_types=1);

use We\Wechat\WxOpenConfig;

$wxOpen = new WxOpenConfig(
    componentAppid: 'wx_component_appid',
    componentAppSecret: 'component_secret',
);
```

获取 component Token 还需要通过 `We\Common\Runtime` 注入 `We\Wechat\WxOpen\ComponentTicketProviderInterface`。授权方代调用同时需要 `We\Wechat\WxOpen\StoreTokenInterface` 保存 refresh Token 和刷新结果。

## 微信支付 APIv3

```php
<?php

declare(strict_types=1);

use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Wechat\WxPayConfig;

$wxPay = new WxPayConfig(
    appid: 'wx_appid',
    mchId: '1900000001',
    merchantSigner: new PemSigningKeyProvider('merchant-serial', $merchantPrivateKey),
    platformTrust: new StaticTrustMaterialProvider([
        'wechat.payment' => ['platform-serial' => $platformPublicKey],
    ]),
);
```

商户签名 Provider 只需实现 `keyId()` 与 `sign()`，可由 HSM 或 KMS 适配器实现。`mchId` 和 Provider 返回的密钥 ID 必须能安全写入微信支付请求头，签名结果必须是非空 Base64。信任材料 Provider 按平台序列号解析公钥，未知序列号失败关闭。

## 支付宝

```php
<?php

declare(strict_types=1);

use We\Alipay\AliPayConfig;
use We\Alipay\AliRestConfig;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\StaticTrustMaterialProvider;

$signer = new PemSigningKeyProvider('application', $appPrivateKey, true);
$gatewayTrust = new StaticTrustMaterialProvider([
    'alipay.gateway' => ['default' => $alipayPublicKey],
], true);
$restTrust = new StaticTrustMaterialProvider([
    'alipay.rest' => ['default' => $alipayPublicKey],
], true);

$aliPay = new AliPayConfig('ali_appid', $signer, $gatewayTrust);
$aliRest = new AliRestConfig('ali_appid', $signer, $restTrust);
```

支付宝用户和代调用应用 Token 由 `We\Alipay\Common\TokenProviderInterface` 解析。SDK 提供 `We\Alipay\Common\StaticTokenProvider`；Provider 负责刷新生命周期，SDK 只在发送前按凭证 ID 读取当前有效且不含控制字符的 Token。

Gateway 默认使用信任材料 ID `default`、字符集 `utf-8`、签名类型 `RSA2`、响应格式 `JSON` 和版本 `1.0`；`charset` 与 `version` 必须是无控制字符的非空值，响应格式只支持 JSON 或 XML，签名类型只支持 RSA 或 RSA2。REST 默认信任材料 ID 同样为 `default`。证书模式可通过 `appCertificateSerial` 和 Gateway 的 `alipayRootCertificateSerial` 写入协议字段；REST `appid` 和应用证书序列号不能包含请求头分隔符。

## 端点

六种配置都可传入 `We\Common\Config\Endpoint`。它只保存规范化掉尾部 `/` 的 `baseUri`，并要求使用没有用户信息、查询参数、片段和普通或编码路径越级段的 HTTPS URL。自定义端点由部署配置显式提供，不从单次请求覆盖。

## 数组配置

`fromArray()` 适合读取字符串配置文件。它固定创建本地 PEM 签名 Provider 和静态信任材料 Provider；HSM、KMS 或动态信任材料应改用构造函数注入。所有输入值必须是字符串；同一格中使用 `/` 分隔的字段名为输入别名：

| 配置 | 必填字段 | 可选字段 |
| --- | --- | --- |
| `WeChatConfig` / `WxAppConfig` | `appid`、`appsecret` / `app_secret` | `storage_scope` / `storageScope`、`token_strategy`、`endpoint` |
| `WxOpenConfig` | `component_appid`、`component_appsecret` / `component_app_secret` | `storage_scope` / `storageScope`、`endpoint` |
| `WxPayConfig` | `appid`、`mch_id` / `mchid`、`merchant_serial` / `cert_serial`、`merchant_private_key` / `cert_private`、`platform_serial`、`platform_public_key` / `platform_certificate` | `endpoint` |
| `AliPayConfig` | `appid` / `app_id`、`private_key` / `merchant_private_key`、`alipay_public_key` | `alipay_cert_sn` / `trust_key_id`、`app_cert_sn` / `signing_key_id`、`charset`、`sign_type`、`format`、`version`、`alipay_root_cert_sn`、`gateway` / `endpoint` |
| `AliRestConfig` | `appid` / `app_id`、`private_key` / `merchant_private_key`、`alipay_public_key` | `alipay_cert_sn` / `trust_key_id`、`app_cert_sn` / `signing_key_id`、`endpoint` |

支付宝配置中的 `app_cert_sn` 同时用作签名密钥 ID 和应用证书序列号；`signing_key_id` 只设置签名密钥 ID。

`WeChatTokenStrategy::Standard` 使用微信标准 Token 端点，`WeChatTokenStrategy::Stable` 使用稳定版 Token 端点。`storageScope` 只参与 Token 缓存键，不会发送给平台。

微信支付数组配置要求 PEM 私钥以及 PEM 公钥或证书。支付宝数组配置同时接受 PEM 和没有 PEM 边界的 Base64 密钥内容。这些内置 Provider 的无效材料在配置阶段失败；构造函数注入的自定义 Provider 负责自身可用性，其 Token、签名和密钥 ID 会在进入最终 HTTP 报文前再次校验。

私钥、公钥、证书、Token 和完整 Secret 不应写入日志或异常 `context`。
