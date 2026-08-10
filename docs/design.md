# 设计

## 定位

2.0 把 SDK 收敛为协议层模块：少量稳定入口承载认证、HTTP、签名、验签和加解密复杂度，业务接口名称和字段继续以平台官方文档为准。

```text
Application
    |
    v
We\Client -- ConfigInterface
    |
    +-- WeChat platform / wxapp / service clients
    +-- WeChat Pay APIv3 client
    +-- Alipay platform / payment clients
    |
    +-- StoreCacheInterface / StoreTokenInterface
    +-- injected Guzzle ClientInterface
```

`Client` 提供六个显式类型工厂，也保留动态 `get()` 供配置驱动系统使用。删除魔术 `__call()` 后，错误工厂名称能在开发阶段更早暴露。

## 模块边界

| 模块 | 负责 | 不负责 |
|------|------|--------|
| `Config` | 必填字段、URL、RSA 和平台信任材料校验 | 读取环境变量、密钥轮换 |
| 平台客户端 | token、请求协议、响应解析、平台签名 | 业务实体和数据库 |
| `Support` | 密钥规范化、缓存、签名、XML、加解密 | 业务流程编排 |
| 缓存契约 | token TTL 和刷新互斥 | 通用应用缓存 API |
| Token 契约 | 授权方 refresh token 读写 | 账号数据模型 |
| 异常层级 | 统一捕获和平台诊断上下文 | 日志与告警策略 |

## 安全决策

支付数据采用强安全默认值：

- 支付宝响应必须存在预期节点、标量 code 和有效签名。
- 微信支付普通 JSON 响应必须包含完整平台签名头，并以原始 body 验签。
- 微信支付通知先验签，再检查默认 300 秒时间窗口，最后解密 resource。
- 缺少信任材料在配置阶段失败，不允许“未配置即跳过验签”。
- 支付密钥不仅要求 OpenSSL 可解析，还要求算法确为 RSA。

通知幂等保留在业务层。SDK 不知道订单聚合、合法状态转换或事务边界，因此不能可靠代替业务持久化去重。

## URL 边界

通用微信平台和 JSON 客户端只接受相对 path，避免调用方参数把受信客户端变成任意 URL 请求器。

微信账单的绝对 URL 是官方协议要求，因此封装在专用 `downloadBill()` 内：只有先通过已签名 API 响应取得的有效 HTTPS 地址才会交给下载 HTTP 适配器。该能力不会泄漏到通用客户端。

## 缓存设计

缓存键由部署前缀、平台通道和逻辑用途组成。三段独立编码并使用点号连接，既保留隔离语义，又满足 PSR-16 对保留字符的限制。

`FileCacheStore` 通过临时文件和原子重命名发布新值。过期读不做按路径删除，避免旧读者在新值重命名后误删新文件。

## 扩展接缝

- 框架缓存实现 `StoreCacheInterface`，或用 `PsrSimpleCacheStore` 适配 PSR-16。
- 微信服务平台账号仓库实现 `StoreTokenInterface`。
- 测试或特殊传输策略向根 `Client` 注入 Guzzle `ClientInterface`。
- 新官方 API 优先通过现有平台客户端的 `get()`、`post()`、`call()`、`raw()`、`download()` 或 `upload()` 表达。

只有当新协议形态无法由现有公开接口安全表达时，才增加专用方法，例如两阶段账单下载。

## 版本边界

2.0 不移植 1.x 上百个业务接口类，不保留旧命名空间、静态 `instance()` 或全局 helper。主版本断代使协议层接口保持可发现、可测试和较小的维护面。
