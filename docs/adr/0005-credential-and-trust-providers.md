---
status: accepted
---

# 外部凭证与信任材料使用小接口

HTTP、支付宝授权 Token、微信 component ticket、签名密钥、信任材料和授权方 Token 存储是部署边界，因此通过最小接口注入。SDK 提供本地 PEM、静态 Token、静态信任材料、静态 ticket 和缓存适配器；授权方 Token 持久化由业务系统实现。

## 影响

- 签名 Provider 只要求密钥 ID 与签名，不要求私钥可导出。
- 信任材料 Provider 按平台通道与序列号或密钥 ID 返回公钥，未知 ID 失败关闭。
- Token 刷新策略由业务 Provider 决定，SDK 不提供通用刷新框架。
- 微信 access Token、component Token 和 authorizer Token 的固定生命周期继续由内部管理器处理。
