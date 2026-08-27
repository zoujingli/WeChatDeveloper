---
status: accepted
---

# 结果解析属于统一 Response

调用者在 `call()` 后通过 `Response::json()`、`xml()` 或 `raw()` 读取结果。通道依据已完成协议校验的响应自动识别结构，不在 `Request` 中预先声明结果类型。

## 影响

- 删除 JSON、XML、空响应、原始字节和流模式对应的结果包装类。
- 结构化平台错误在 `Response` 暴露前识别。
- 原始字节仍经过支付通道强制验签。
- 支付宝支付 v2 官方未签名媒体响应必须在 `Request` 上显式调用 `rawMedia()`。
- 下载结果仍由同一 `Response` 提供字节数和摘要。
