---
status: accepted
---

# 使用单一调用接口

六个通道分别使用 `We` 根命名空间中的 `*Client::mk(具体配置)->call(Request)->Response`。`We\Common\Request` 表达调用，`We\Common\Response` 承担结果解析，公共运行依赖由可选 `We\Common\Runtime` 复用。

这取代根配置类型分派、HTTP 动词方法和目标、请求体、身份、结果类族。调用方只导入正在使用的通道 Client。

## 影响

- 六个通道集合保持封闭，每个 Client 只接受自己的配置类型。
- `src/` 根目录只保存六个 Client 文件；具名 Config 直接位于 `Wechat` 或 `Alipay` 生态根目录，跨生态公开调用类型集中在 `Common`。
- `Request` 使用字符串相对目标；绝对资源只能使用可信 `Resource`。
- `Request` 的请求体保持唯一，修改方法返回新实例。
- JSON、XML 和原始字节在 `Response` 端读取，不预先组合结果模式。
- 已发送请求不会因平台业务错误被隐式重放。
