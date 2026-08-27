# 异常

运行时 SDK 异常统一位于 `We\Common\Exception`，并按失败阶段分类：

```text
SdkException
├── ConfigurationException
├── InvalidCallException
├── TransportException
├── ProtocolException
│   └── SignatureException
├── PlatformException
└── StreamException
```

| 异常 | 含义 |
| --- | --- |
| `ConfigurationException` | 配置、凭证、Provider 或端点无效 |
| `InvalidCallException` | 目标、请求体、身份、保留请求头或资源组合无效，尚未发生 I/O |
| `TransportException` | DNS、TLS、连接或超时失败 |
| `ProtocolException` | 编码、JSON/XML 解析或响应结构无效 |
| `SignatureException` | 请求签名无法生成，或无法建立平台响应信任 |
| `PlatformException` | 可信平台响应拒绝调用 |
| `StreamException` | 暂存、上限、摘要或目标流失败 |

```php
<?php

declare(strict_types=1);

use We\Common\Exception\PlatformException;
use We\Common\Exception\SignatureException;
use We\Common\Exception\TransportException;

try {
    $data = $client->call($request)->json();
} catch (SignatureException $exception) {
    security_log($exception->channel(), $exception->requestId());
} catch (TransportException $exception) {
    retryable_log($exception->context());
} catch (PlatformException $exception) {
    platform_log($exception->platformCode(), $exception->context());
}
```

`SdkException` 提供 `channel()`、`requestId()`、`platformCode()` 和脱敏 `context()`。平台响应一旦可用，后续协议解析、验签、平台拒绝和流处理异常都会携带可提取的 request ID；微信 Token 内部调用遵循同一规则。诊断上下文不包含私钥、完整 Token、签名材料、文件内容或完整业务请求体。

自定义 Token 或签名 Provider 返回非法值时分别抛出 `ConfigurationException` 或 `SignatureException`。最终 PSR-7 请求参数拒绝报文时转换为 `InvalidCallException`，不向调用方泄漏偶发的底层参数异常。

`TransportException` 只表示本次调用的传输失败，不代表请求一定没有到达平台。SDK 不自动重放；调用方必须依据业务幂等性和平台查询结果决定是否重试。`PlatformException` 表示已经识别到可信平台拒绝，不应按网络故障盲目重试。

原生 `TypeError` 和调用不存在的方法属于编程错误，不包装为运行时 SDK 异常。
