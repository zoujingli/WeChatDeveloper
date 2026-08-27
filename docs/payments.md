# 微信支付 APIv3

微信支付通道为相对路径生成商户签名，并在解析前按平台序列号验证响应。

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\WxPayClient;

$payment = WxPayClient::mk($wxPayConfig);
$transaction = $payment->call(
    Request::post('v3/pay/transactions/jsapi')->json([
        'appid' => 'wx_appid',
        'mchid' => '1900000001',
        'description' => 'example',
        'out_trade_no' => 'ORDER-1',
        'notify_url' => 'https://merchant.example.com/notify',
        'amount' => ['total' => 1],
    ]),
)->json();
```

示例路径与请求体由调用方依据官方文档提供；SDK 不增加下单、退款等方法。

## 信任

- 请求签名覆盖最终 HTTP 方法、路径与查询参数，以及实际请求体字节。
- `Authorization` 与 `Wechatpay-Serial` 为保留请求头。
- 普通 API 响应必须包含完整的微信支付验签响应头。
- `raw()` 读取原始字节也不能跳过验签。
- `sensitiveKey()` 只让通道写入官方敏感字段密钥 ID 请求头；字段加密由应用完成。

## 派生下载

账单等两阶段资源先从可信 JSON 响应创建：

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Utils;
use We\Common\Request;

$source = $payment->call(Request::get('v3/bill/tradebill')->query([
    'bill_date' => '2026-08-26',
]));

$resource = $source->signedResource('download_url', 'hash_value');
$destination = Utils::streamFor('');
$download = $payment->call(
    Request::get($resource)->downloadTo($destination),
);

$bytes = $download->bytesWritten();
$digest = $download->digest();
```

资源必须属于当前通道。摘要不匹配、字面私网或保留 IP、额外查询参数会在复制前被拒绝；目标流写入失败遵循[公开 API 的流所有权约定](api.md#request)。

## 边界

支付通知验签解密、前端 JSAPI/APP 参数、业务订单和 ACK 不属于出站 API 调用层。
