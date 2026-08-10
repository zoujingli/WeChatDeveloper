# 异常

2.0 使用 `We\Exception\SdkException` 作为所有 SDK 故障的统一基类，同时保留平台和故障类别。

```text
RuntimeException
└── SdkException
    ├── TransportException
    ├── WechatException
    │   ├── ApiException
    │   └── SignatureException
    └── AlipayException
        ├── AlipayApiException
        └── AlipaySignatureException
```

| 类型 | 典型场景 |
|------|----------|
| `SdkException` | 根客户端、缓存适配或通用 SDK 配置错误 |
| `TransportException` | 与微信或支付宝建立连接、发送请求或下载文件失败 |
| `WechatException` | 微信配置、协议、加解密或请求故障 |
| `ApiException` | 微信平台/支付 API 返回错误或无效响应 |
| `SignatureException` | 微信消息、支付响应或通知验签失败 |
| `AlipayException` | 支付宝配置、密钥、签名或解密故障 |
| `AlipayApiException` | 支付宝网关响应结构或业务错误 |
| `AlipaySignatureException` | 支付宝同步响应缺少签名或验签失败 |

## 统一捕获

```php
<?php

declare(strict_types=1);

use We\Exception\SdkException;

try {
    $result = $platform->get('cgi-bin/user/get');
} catch (SdkException $exception) {
    logger()->error($exception->getMessage(), [
        'exception' => $exception,
    ]);
}
```

## 精确捕获和上下文

```php
<?php

declare(strict_types=1);

use We\Exception\AlipayApiException;
use We\Exception\AlipaySignatureException;
use We\Exception\TransportException;

try {
    $result = $alipay->post('alipay.trade.query', [
        'out_trade_no' => 'A202608100001',
    ]);
} catch (AlipaySignatureException $exception) {
    security_log($exception->getMessage(), $exception->context());
} catch (AlipayApiException $exception) {
    application_log($exception->getMessage(), $exception->context());
} catch (TransportException $exception) {
    retryable_log($exception->getMessage(), $exception->context());
}
```

异常的 `context()` 返回平台响应、签名序列号或其他诊断数据。不要把可能含敏感信息的完整上下文直接写入公开日志。

PHP 原生参数类型错误、调用不存在的方法等编程错误不包装为 `SdkException`；这类错误应在开发和静态分析阶段修复。
