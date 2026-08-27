# 更新记录

## 2.0.0（尚未发布）

### 新调用标准

- 六个通道直接使用 `We` 根命名空间中的 `WeChatClient::mk()`、`WxAppClient::mk()`、`WxOpenClient::mk()`、`WxPayClient::mk()`、`AliPayClient::mk()` 和 `AliRestClient::mk()`。
- Client 与 Config 统一使用微信公众号、微信小程序、微信开放平台、微信支付、支付宝支付 v2 和支付宝 REST v3 的场景名称；Config 直接位于生态根目录，底层通道 ID 保持不变。
- `src/` 根目录只保存通道 Client；请求、响应、资源、运行依赖和 multipart 部件统一位于 `We\Common`。
- 源码按跨生态 `Common`、生态公共 `Wechat/Common` 或 `Alipay/Common`、通道专属实现目录三层归属，只有存在专属契约或实现时才建立通道目录。
- 公开配置、扩展接口和异常采用与生态目录一致的完整命名空间，接口 PHPDoc 明确单位、所有权和失败语义。
- 各通道共享 `ChannelInterface`、`Request`、`Response`、`Runtime` 和内部调用管线，不再使用根配置分派。
- `Request` 直接表达相对目标、六种请求体、调用身份、超时和下载目标。
- `Response` 统一提供 JSON、XML、原始字节、元数据和派生资源解析。

### 协议与安全

- 微信 access Token、开放平台 component Token 和 authorizer Token 继续使用 TTL 缓存和互斥锁。
- 微信支付 APIv3、支付宝支付 v2 Gateway 和支付宝 REST v3 集中处理请求签名与响应验签。
- 保留可替换的 HTTP、Token、签名、信任材料、component ticket 和授权方 Token 存储接口。
- 下载在受限临时文件中完成错误识别和摘要校验，成功后才写入目标流。
- 平台保留请求头、调用方直接提供的绝对 URL、字面私网或保留 IP 资源和未识别信任材料采用失败关闭。

### 精简

- 删除 `Call` 的目标、请求体、身份和响应类族，以及五类结果包装。
- 删除通知、消息、授权 URL、客户端调起和字段加解密等非出站 API 能力。
- 删除业务端点快捷方法、字符串通道分派、Guzzle 选项透传和隐式重放。
- 删除没有多态调用方的 `ConfigInterface`、未参与调用的端点环境名称，以及 Runtime 的公开临时目录查询方法。
- PHP 最低版本升级为 8.1，CI 覆盖 PHP 8.1、8.2、8.3、8.4。
