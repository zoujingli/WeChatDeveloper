# 平台 API 调用场景事实矩阵

研究日期：2026-08-26

状态：已完成

## 范围

本文只确认出站 API 调用层必须表达的报文协议，不枚举业务端点。资料限定为微信官方文档、微信支付商户文档和支付宝官方 SDK。

通知、消息、浏览器授权、客户端调起和业务字段加解密不是出站 API 调用，明确排除在实现范围外。

## 场景矩阵

| 通道 | 目标与方法 | 请求 | 响应 | 认证与信任 | 一手证据 |
| --- | --- | --- | --- | --- | --- |
| 微信公众号/小程序 | `api.weixin.qq.com` 相对路径，主要使用 GET/POST | 查询参数中的 Token、JSON、`multipart`、文件 | JSON、二进制、成功二进制/失败 JSON | access Token；JSON `errcode` | [access Token](https://developers.weixin.qq.com/miniprogram/dev/server/API/mp-access-token/api_getaccesstoken)、[素材上传](https://developers.weixin.qq.com/doc/service/api/material/temporary/api_uploadtempmedia.html)、[小程序码](https://developers.weixin.qq.com/miniprogram/dev/server/API/qrcode-link/qr-code/api_getunlimitedqrcode.html) |
| 微信开放平台 | `cgi-bin/component/...` 及授权方 API | component/authorizer Token、JSON | JSON | component ticket、component Token、authorizer Token | [component Token](https://developers.weixin.qq.com/doc/oplatform/openApi/ticket-token/api_getcomponentaccesstoken.html)、[authorizer Token](https://developers.weixin.qq.com/doc/oplatform/openApi/ticket-token/api_getauthorizeraccesstoken.html) |
| 微信支付 APIv3 | REST 路径，GET/PUT/POST/PATCH/DELETE | 查询参数、JSON、原始字节或流、平台请求头 | JSON、原始字节或流 | 商户签名请求；平台序列号对应公钥验签响应 | [官方 PHP SDK](https://github.com/wechatpay-apiv3/wechatpay-php)、[签名实现](https://github.com/wechatpay-apiv3/wechatpay-php/blob/main/src/ClientJsonTrait.php) |
| 支付宝支付 v2 | OpenAPI 方法，Gateway GET/POST | AOP 参数、`biz_content`、表单/`multipart` | JSON/XML 的 `{method}_response` 或 `error_response` 签名节点，以及官方未签名媒体 | 应用私钥签名；支付宝公钥/证书验签响应 | [AopClient](https://github.com/alipay/alipay-sdk-php-all/blob/master/v2/aop/AopClient.php) |
| 支付宝 REST v3 | REST 路径，多种 HTTP 方法 | 查询参数、JSON、`multipart` 的 `data` 与文件部件、应用授权请求头 | HTTP 状态码/响应头与 JSON/资源 | HTTP 报文签名；`multipart` 只签 `data`；响应头验签 | [v3 通用执行器](https://github.com/alipay/alipay-sdk-php-all/blob/master/v3/src/Util/GenericExecuteApi.php)、[v3 签名](https://github.com/alipay/alipay-sdk-php-all/blob/master/v3/src/Util/AlipayConfigUtil.php) |

## 必要表达力

统一请求必须覆盖：

- 任意合法 HTTP 方法、相对路径或 Gateway 方法；
- 保序查询参数、普通请求头；
- 互斥的空请求体、JSON、表单、原始字节、流和 `multipart` 请求体；
- 默认、匿名、微信授权方、支付宝用户和支付宝代调用应用身份；
- JSON、XML、空响应、原始字节、成功二进制/失败 JSON 响应；
- 同步下载、大小上限和摘要验证。

因此 `Request` 保留这些报文维度，但不为每个组合建立类型。响应结构在协议校验后由 `Response` 解析。

## 二阶段资源

| 来源 | 第二阶段规则 | 结论 | 一手证据 |
| --- | --- | --- | --- |
| 微信支付账单 | 对完整下载 URL 重新生成 APIv3 `Authorization`；下载响应免平台签名；校验第一阶段哈希 | 资源需要重新签名标记和摘要 | [申请账单](https://pay.weixin.qq.com/doc/v3/merchant/4013071227)、[下载账单](https://pay.weixin.qq.com/doc/v3/merchant/4013071238) |
| 微信临时素材 | 第一阶段可能返回后续下载 URL | 资源可能匿名，但必须限制来源通道和网络地址 | [获取临时素材](https://developers.weixin.qq.com/doc/service/api/material/temporary/api_getmedia.html) |
| 支付宝账单 | 查询返回短期下载地址 | 需要通用派生资源，而不是 `downloadBill()` | [v2 账单请求](https://github.com/alipay/alipay-sdk-php-all/blob/master/v2/aop/request/AlipayDataDataserviceBillDownloadurlQueryRequest.php) |

## 凭证生命周期

- 微信 access Token 支持标准 GET 和 stable POST JSON，按 `expires_in` 缓存并互斥刷新。[标准 Token](https://developers.weixin.qq.com/miniprogram/dev/server/API/mp-access-token/api_getaccesstoken)、[Stable Token](https://developers.weixin.qq.com/doc/service/api/base/api_getstableaccesstoken.html)
- component Token 依赖 component verify ticket；authorizer Token 依赖业务保存的 refresh Token，完整校验后才持久化刷新结果。[component ticket](https://developers.weixin.qq.com/doc/oplatform/Third-party_Platforms/2.0/Before_Develop/component_verify_ticket.html)
- 支付宝 `auth_token` 与 `app_auth_token` 是不同身份域，v3 应用授权 Token 位于请求头。[v2/v3 SDK](https://github.com/alipay/alipay-sdk-php-all)

## 工程结论

1. 六个独立通道 Client 共用 `call(Request)->Response` 足以承载当前六种协议。
2. `Promise` 不是报文协议要求，同步调用即可覆盖场景。
3. Token 可在发送前刷新；已经发送的业务请求不自动重放。
4. 原始字节读取不能关闭微信支付或支付宝 REST 的强制验签。
5. 支付宝支付 v2 未签名媒体响应需要显式 `rawMedia()`，不能由 `Content-Type` 静默放宽。
6. 信任材料必须按序列号或密钥 ID 解析，并允许调用方实现轮换适配器。

## 限制

本文证明协议形态，不保证逐个枚举所有官方业务端点。平台新增报文协议时需要扩展 SDK；仅新增路径、方法或请求字段时无需增加公共方法。
