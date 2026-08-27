---
status: accepted
---

# 异常按失败阶段分类

公共异常按配置、调用校验、传输、协议、签名、平台拒绝和流处理分类，不为微信与支付宝复制平行异常树。

## 影响

- 所有运行时 SDK 异常继承 `SdkException`。
- `channel`、`requestId`、`platformCode` 和脱敏 `context` 提供结构化诊断。
- 私钥、完整 Token、签名材料、文件和完整业务请求体不进入 `context`。
- 编程错误保留原生 `TypeError` 或 `Error`。
