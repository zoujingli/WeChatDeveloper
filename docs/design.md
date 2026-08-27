# 设计

## 边界

2.0 是平台 API 出站调用层。它隐藏 Token、编码、HTTP、签名验签、可信错误和流处理，但不实现平台业务端点，也不处理通知、消息、授权页面、客户端调起或业务状态。

```text
业务应用
    |
    v
对应通道 *Client::mk(Config) -> call(Request) -> Response
                                      |
                                      +-- 共享 Runtime / HTTP 传输
                                      +-- Token / 签名 / 信任材料适配器
```

## 六个通道

通道按报文协议划分：微信公众号 `wechat.platform`、小程序 `wechat.wxapp`、微信开放平台 `wechat.service`、微信支付 `wechat.payment`、支付宝支付 v2 `alipay.gateway` 和支付宝 REST v3 `alipay.rest`。底层通道 ID 保持协议稳定，不随公开类名变化。

每个通道有独立 Client 与对应配置，不经过根类型分派。`src/` 根目录只保存六个 `We\*Client`；公共调用类型统一位于 `We\Common`，包括 `Request`、`Response`、`Resource`、`Runtime` 和 `MultipartPart`。

源码按“跨生态公共、生态公共、通道专属”三层归属：

```text
src/
├── *Client.php                    # 仅六个通道入口
├── Common/                        # 微信与支付宝共同使用
├── Wechat/
│   ├── WeChatConfig.php           # 微信公众号配置
│   ├── WxAppConfig.php            # 微信小程序配置
│   ├── WxOpenConfig.php           # 微信开放平台配置
│   ├── WxPayConfig.php            # 微信支付配置
│   ├── Common/                    # 微信生态共同使用
│   └── WxOpen/                    # 开放平台专属契约与实现
└── Alipay/
    ├── AliPayConfig.php           # 支付宝支付 v2 配置
    ├── AliRestConfig.php          # 支付宝 REST v3 配置
    └── Common/                    # 支付宝生态共同使用
```

跨通道基类、调用值对象、传输、协议、异常和签名 Provider 放在 `Common`。只被同一生态多个通道复用的类型放在该生态的 `Common`。具名 Config 直接放在生态根目录，因为类名已经表达通道；单一通道另有专属契约或实现时才建立同名前缀目录。非公开实现继续放入所属层级的 `Internal`，例如微信通用 Token 管理位于 `Wechat/Common/Internal`，开放平台独有的 Token 管理位于 `Wechat/WxOpen/Internal`。不得建立顶层 `Config`、`Contract`、`Provider` 等按技术角色横切生态的目录。

## 固定顺序

每次调用按同一顺序执行：

1. 校验目标、请求体、身份和保留请求头。
2. 在发送前解析或刷新凭证。
3. 确定性编码查询参数、请求头和唯一请求体。
4. 按平台通道要求，对最终 URI、请求头和协议规定的报文字节生成签名。
5. 通过同步 HTTP 传输发送一次请求。
6. 暂存响应，并按平台通道要求完成强制验签。
7. 识别可信平台错误和响应结构。
8. 返回统一 `Response`，或在验证后复制下载流。

已经发送的业务请求不会被隐式重放。

## 请求与响应

`Request` 是不可变值对象，目标只接受相对路径、支付宝 Gateway 方法或可信 `Resource`。查询参数保序并支持重复键；请求只有一个请求体。

`Response` 保存已接受的原始字节与解析值。JSON 和 XML 结构由响应自动识别，调用方在结果端选择解析方法，不需要响应模式或结果类族。

下载先写入权限受限的临时文件。JSON 错误识别、响应验签和摘要校验成功后才写入调用方流。

## 信任边界

- 调用方不能覆盖认证、`Host`、`Content-Length`、`Content-Type` 或平台签名请求头。
- 普通目标不能使用绝对 URL。
- 派生资源绑定来源通道，强制 HTTPS，默认拒绝字面私网、保留或回环 IP 地址以及重定向。
- 未识别的序列号或密钥 ID 失败关闭。
- 原始字节读取不会关闭支付通道的强制验签。
- 支付宝支付 v2 只有显式 `rawMedia()` 才采用官方未签名媒体响应规则。

## 扩展

HTTP、缓存、Token、签名密钥、信任材料、component ticket 和授权方 Token 存储是实际外部边界，因此使用小接口。业务请求体、端点目录、日志、持久化和控制器不建立 SDK 扩展点。

长期决策见 [ADR](adr/)，协议依据见 [事实矩阵](research/platform-call-scenarios.md)。
