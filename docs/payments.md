# 微信支付 APIv3

微信支付客户端对商户请求签名，并在信任普通 JSON 响应前校验微信支付平台签名。配置缺少平台信任材料或序列号时会立即失败。

## 配置

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPaymentConfig;

$config = new WechatPaymentConfig(
    appid: 'wx_appid',
    mchId: '1900000001',
    apiV3Key: '0123456789abcdef0123456789abcdef',
    merchantSerial: 'merchant_certificate_serial',
    merchantPrivateKey: $merchantPrivateKeyPem,
    platformPublicKey: $wechatPayPlatformPublicKeyPem,
    platformSerial: 'wechatpay_platform_serial',
    notificationToleranceSeconds: 300,
);

$payment = (new Client())->wechatPayment($config);
```

商户私钥和序列号用于 `WECHATPAY2-SHA256-RSA2048` 请求签名。平台公钥或平台证书及其序列号用于响应和通知验签，两类材料不可混用。

## API 调用与响应信任

```php
<?php

declare(strict_types=1);

$order = $payment->post('v3/pay/transactions/jsapi', [
    'appid' => 'wx_appid',
    'mchid' => '1900000001',
    'description' => '测试订单',
    'out_trade_no' => 'T202608100001',
    'notify_url' => 'https://example.com/wechat-pay/notify',
    'amount' => ['total' => 1, 'currency' => 'CNY'],
    'payer' => ['openid' => 'openid'],
]);

$query = $payment->get('v3/pay/transactions/out-trade-no/T202608100001', [
    'mchid' => '1900000001',
]);
```

普通 `request()`、`get()` 和 `post()` 响应必须包含：

- `Wechatpay-Timestamp`
- `Wechatpay-Nonce`
- `Wechatpay-Signature`
- `Wechatpay-Serial`

SDK 使用原始响应 body 验签，校验配置中的平台序列号，然后才解析 JSON。缺头、序列号不匹配或签名失败会抛出 `SignatureException`；HTTP 错误响应也先验签，再抛出带平台错误上下文的 `ApiException`。

`raw()` 和旧的单阶段 `download()` 只负责商户请求签名并返回原始响应，不把 body 解释为可信业务 JSON。需要业务数据时应使用 `get()`、`post()` 或 `request()`。

## 两阶段账单下载

微信支付账单不是“对申请接口直接下载文件”。应使用 `downloadBill()`：

```php
<?php

declare(strict_types=1);

$response = $payment->downloadBill('v3/bill/tradebill', [
    'bill_date' => '2026-08-09',
    'bill_type' => 'ALL',
]);

$billContents = (string) $response->getBody();
```

流程如下：

1. SDK 对申请账单地址的 APIv3 请求生成商户签名。
2. SDK 验证元数据响应的平台签名并读取 `download_url`。
3. SDK 只接受有效的 HTTPS 下载地址。
4. SDK 禁止下载响应重定向，避免已验证的 HTTPS 地址把请求降级到 HTTP 或其他目标。
5. SDK 使用专用下载能力获取文件并返回 PSR-7 响应。

这不会放宽通用 JSON 客户端的相对 path 限制。元数据中的非 HTTPS、无 host 或含用户信息的 URL 会被拒绝；3xx 下载响应会作为接口错误返回，不会自动跟随。

## 支付通知

通知验签必须使用收到的原始 HTTP body，不能先 `json_decode()` 再编码。示例中的请求头数组应来自 Web 框架原始请求：

```php
<?php

declare(strict_types=1);

$rawBody = (string) file_get_contents('php://input');
$headers = [
    'Wechatpay-Timestamp' => (string) ($_SERVER['HTTP_WECHATPAY_TIMESTAMP'] ?? ''),
    'Wechatpay-Nonce' => (string) ($_SERVER['HTTP_WECHATPAY_NONCE'] ?? ''),
    'Wechatpay-Signature' => (string) ($_SERVER['HTTP_WECHATPAY_SIGNATURE'] ?? ''),
    'Wechatpay-Serial' => (string) ($_SERVER['HTTP_WECHATPAY_SERIAL'] ?? ''),
];

$resource = $payment->post('decrypt_notification', [], [
    'headers' => $headers,
    'raw_body' => $rawBody,
]);
```

处理顺序是平台签名校验、时间窗口校验、JSON 解析、APIv3 resource 解密。默认允许通知时间戳与当前时间相差最多 300 秒，过去和未来都受限制。

可以根据部署延迟显式扩大窗口。只有 `notificationToleranceSeconds: 0` 会关闭时间检查，RSA 验签仍然执行。关闭时间检查适合受控回放，不应作为生产默认值。

SDK 的时间窗口只降低签名通知重放风险，不能替代业务幂等。业务系统仍应按通知 ID、商户订单号和当前订单状态做持久化去重与状态转换。

## 敏感信息

向微信支付发送使用平台公钥加密的敏感字段时，按官方文档在请求 headers 传对应平台证书/公钥序列号：

```php
<?php

declare(strict_types=1);

$result = $payment->post('v3/example/with-sensitive-information', $payload, [
    'headers' => [
        'Wechatpay-Serial' => 'wechatpay_platform_serial',
    ],
]);
```

这里的 `Wechatpay-Serial` 不是商户证书序列号。
