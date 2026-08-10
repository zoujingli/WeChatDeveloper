# 支付宝

支付宝客户端统一构造网关公共参数，使用应用私钥签名请求，并使用支付宝公钥验证同步响应和异步通知。

## 开放平台配置

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\AlipayPlatformConfig;

$config = new AlipayPlatformConfig(
    appid: '2026000000000000',
    privateKey: $applicationPrivateKey,
    alipayPublicKey: $alipayPublicKey,
    signType: 'RSA2',
);

$alipay = (new Client())->alipayPlatform($config);
```

`privateKey` 与 `alipayPublicKey` 都是必填安全配置。前者代表应用，后者代表支付宝平台。默认网关是 `https://openapi.alipay.com/gateway.do`。

完整 PEM 和无头尾的 RSA key body 都可使用；私钥支持 PKCS#1 与 PKCS#8。EC 等非 RSA 密钥会在配置阶段被拒绝。

## 网关调用

API method 与支付宝官方文档一致：

```php
<?php

declare(strict_types=1);

$result = $alipay->post('alipay.user.info.share', [
    'auth_token' => 'user_auth_token',
]);

$authUrl = $alipay->auth(
    'https://example.com/alipay/callback',
    'auth_user',
    'state-value',
);
```

同步响应只有在下列条件全部满足时才返回数组：

1. JSON 可以解析。
2. 存在与 API method 对应的响应节点，或明确的 `error_response`。
3. 顶层存在签名，且原始响应节点通过支付宝公钥验签。
4. 响应节点存在标量 `code`。
5. `code` 等于 `10000`。

空 JSON、缺节点或缺 code 不会被当作成功，并抛出 `AlipayApiException`。缺签名或验签失败抛出 `AlipaySignatureException`。网络传输错误抛出 `TransportException`；平台业务错误的异常保留响应节点上下文。

## 支付客户端

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\AlipayPaymentConfig;

$payment = (new Client())->alipayPayment(new AlipayPaymentConfig(
    appid: '2026000000000000',
    privateKey: $applicationPrivateKey,
    alipayPublicKey: $alipayPublicKey,
));

$pageUrl = $payment->page([
    'out_trade_no' => 'A202608100001',
    'total_amount' => '0.01',
    'subject' => '测试订单',
    'product_code' => 'FAST_INSTANT_TRADE_PAY',
]);

$refund = $payment->refund([
    'out_trade_no' => 'A202608100001',
    'refund_amount' => '0.01',
]);
```

`page()` 返回已签名的电脑网站支付 URL；`refund()` 调用 `alipay.trade.refund`。其他支付 API 继续使用官方 method 调用 `request()`、`get()` 或 `post()`。

## 异步通知

业务处理前验证支付宝通知完整参数：

```php
<?php

declare(strict_types=1);

$verified = $alipay->verifyNotify($_POST);
if (!$verified) {
    throw new RuntimeException('Invalid Alipay notification signature.');
}
```

验签会排除 `sign` 和 `sign_type`，按支付宝规则排序并拼接其他非空字段。返回 `true` 只证明参数签名有效；订单金额、商户身份、通知状态和业务幂等仍由业务系统校验。
