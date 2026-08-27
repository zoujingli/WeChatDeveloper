# 公开 API

## 通道 Client

六个通道 Client 都实现 `We\Common\Contract\ChannelInterface`，公开入口固定为 `*Client::mk(具体配置, ?Runtime)->call(Request)->Response`：

| 场景 | Client | 配置 | `channel()` |
| --- | --- | --- | --- |
| 微信公众号 | `We\WeChatClient` | `We\Wechat\WeChatConfig` | `wechat.platform` |
| 微信小程序 | `We\WxAppClient` | `We\Wechat\WxAppConfig` | `wechat.wxapp` |
| 微信开放平台 | `We\WxOpenClient` | `We\Wechat\WxOpenConfig` | `wechat.service` |
| 微信支付 | `We\WxPayClient` | `We\Wechat\WxPayConfig` | `wechat.payment` |
| 支付宝支付 v2 | `We\AliPayClient` | `We\Alipay\AliPayConfig` | `alipay.gateway` |
| 支付宝 REST v3 | `We\AliRestClient` | `We\Alipay\AliRestConfig` | `alipay.rest` |

每个 `mk()` 只接受表中对应配置；`Runtime` 省略时按需创建本地默认依赖。没有按配置类型分派的根 Client，也没有业务端点方法。

```php
<?php

declare(strict_types=1);

use We\Wechat\WxAppConfig;
use We\WxAppClient;

$client = WxAppClient::mk(new WxAppConfig('wx_appid', 'app_secret'));
```

`call(Request)` 同步发送一次请求。SDK 不会在已发送后因超时、平台错误或验签失败自动重放；调用方只能在确认业务幂等语义后自行重试。

## Runtime

`We\Common\Runtime` 集中注入可替换运行依赖，同一实例可以复用于多个通道 Client：

| 参数 | 类型 | 省略时的行为 |
| --- | --- | --- |
| `cache` | `We\Wechat\Common\StoreCacheInterface` | 在默认系统临时目录中延迟创建 `We\Wechat\Common\FileCacheStore` |
| `authorizers` | `We\Wechat\WxOpen\StoreTokenInterface` | 使用授权方身份时失败关闭 |
| `transport` | `We\Common\Transport\HttpTransportInterface` | 延迟创建 `We\Common\Transport\GuzzleTransport` |
| `tokens` | `We\Alipay\Common\TokenProviderInterface` | 使用支付宝用户或代调用应用身份时失败关闭 |
| `componentTickets` | `We\Wechat\WxOpen\ComponentTicketProviderInterface` | 获取 component Token 时失败关闭 |
| `resourcePolicy` | `We\Common\Protocol\ExternalResourcePolicyInterface` | 使用 `PublicNetworkResourcePolicy` |
| `cacheKeyPrefix` | `string` | `wechat_developer` |
| `spoolDirectory` | `?string` | 使用系统临时目录下的 SDK 专用子目录 |

```php
<?php

declare(strict_types=1);

use We\Common\Runtime;

$runtime = new Runtime(transport: $transport, cache: $cache);
$client = WxAppClient::mk($config, $runtime);
```

默认资源策略拒绝字面私网、保留或回环 IP 地址。它不执行 DNS 解析；需要抵御 DNS 重绑定的部署应注入更严格的网络策略或在出站代理层限制目标。

## Request

`We\Common\Request` 是不可变值对象。每个修改方法返回新实例，原实例保持不变。

### 创建与目标

| 工厂 | 作用 |
| --- | --- |
| `Request::create(string $method, Resource|string $target)` | 使用任意有效 HTTP 方法创建请求 |
| `Request::get()`、`post()`、`put()`、`patch()`、`delete()` | 使用常见 HTTP 方法创建请求 |

字符串目标必须是没有查询参数、片段、反斜杠、URL scheme 或普通及编码路径越级段的相对路径；支付宝支付 v2 Gateway 进一步要求点分方法名。绝对 URL 只能通过可信 `Response` 创建的 `Resource` 进入调用链。

### 查询参数与请求头

`query()` 和 `form()` 接受关联数组或 `[name, value]` 参数对列表。参数对列表保留顺序和重复键；关联数组中的列表值展开为重复键。`null`、布尔值、整数和浮点数分别转换为空字符串、`1`/`0` 和字符串。

`headers()` 接受 `array<string, string|list<string>>`。认证、`Host`、`Content-Length`、`Content-Type` 和平台签名请求头由通道独占，在发送前发现冲突会抛出 `InvalidCallException`。

### 请求体

后一次请求体修改会替换前一次请求体，最终只发送一种编码结果：

| 方法 | 请求体与所有权 |
| --- | --- |
| `json(mixed $value)` | 在调用时编码一次 JSON |
| `form(array $fields)` | RFC 3986 表单参数 |
| `raw(string $body, string $mediaType = 'application/octet-stream')` | 已编码字符串 |
| `raw(StreamInterface $body, string $mediaType = 'application/octet-stream', ?int $knownLength = null)` | 从当前位置读取；`knownLength` 是剩余字节数，流仍由调用方持有 |
| `multipart(MultipartPart ...$parts)` | 至少一个普通字段或文件部件 |

`We\Common\MultipartPart` 的 `contents` 接受字符串或可读 PSR-7 流；`filename`、`mediaType` 和附加请求头均为可选值。部件流从当前位置读取，生命周期仍由调用方管理。

### 调用身份

| 方法 | 支持通道 | 协议行为 |
| --- | --- | --- |
| 默认身份 | 全部 | 微信平台注入默认 Token；支付和支付宝继续生成平台级签名 |
| `anonymous()` | `wechat.platform`、`wechat.wxapp`、`wechat.service`、两个支付宝通道 | 不注入可选 Token；支付宝应用签名仍保留 |
| `asWechatAuthorizer(string $appid)` | `wechat.service` | 从授权方存储解析 authorizer Token |
| `asAlipayUser(string $credentialId)` | `alipay.gateway`、`alipay.rest` | 从 Token Provider 解析用户 `auth_token` |
| `asAlipayApp(string $credentialId)` | `alipay.gateway`、`alipay.rest` | 从 Token Provider 解析代调用应用 Token |

微信支付只接受默认商户身份。调用方不能通过查询参数或请求头直接写入由身份生成的 Token。

### 协议与流选项

| 方法 | 契约 |
| --- | --- |
| `rawMedia()` | 仅用于支付宝支付 v2 官方明确不签名的媒体响应；仍识别 HTTP 与 JSON/XML 平台错误 |
| `sensitiveKey(string $keyId)` | 仅让微信支付写入敏感字段使用的证书序列号或密钥 ID；不加密业务字段 |
| `timeout(int $milliseconds)` | 设置大于 0 的单次 HTTP 超时；默认 `20000` |
| `maxResponseBytes(int $bytes)` | 设置响应以及需要签名的不可回绕请求流的暂存上限；默认 `67108864` |
| `downloadTo(StreamInterface $destination, ?string $digestAlgorithm = null, ?string $expectedDigest = null)` | 校验成功后复制到可写目标流 |

摘要算法和值必须同时提供。派生资源已经携带摘要时，以资源摘要为准。目标流仍由调用方持有；校验失败前不会写入，复制过程中写入失败时可能保留已复制的部分数据。

## Response

`We\Common\Response` 只表示已完成通道协议校验的响应。通道优先按 `Content-Type` 识别 JSON、XML 和原始字节；缺少该响应头时才按内容保守识别。显式二进制类型不会因首字节类似 JSON/XML 被改判：

| 方法 | 返回与失败语义 |
| --- | --- |
| `json()` | 返回任意 JSON 解析值；非 JSON 响应抛出 `ProtocolException` |
| `xml()` | 返回保留重复节点的数组；非 XML 响应抛出 `ProtocolException` |
| `raw()` | 返回原始字节；可回绕流会恢复读取位置 |
| `body()` | 返回 PSR-7 流；原始字节临时流由调用方关闭，下载响应返回调用方提供的目标流 |
| `channel()` | 稳定平台通道标识 |
| `status()` | 平台原始 HTTP 状态码 |
| `headers()`、`header(string $name)` | 全部响应头或不区分大小写的单个响应头 |
| `requestId()` | 平台请求 ID，缺失时为 `null` |
| `keyId()` | 验签使用的序列号或密钥 ID，未验签时为 `null` |
| `bytesWritten()` | 下载写入字节数，非下载响应为 `null` |
| `digest()` | 下载摘要，未要求摘要校验时为 `null` |

支付通道的 `raw()` 与 `body()` 不能绕过响应验签。支付宝支付 v2 只有显式 `rawMedia()` 请求可以采用官方未签名媒体规则。

### 派生资源

`resource(string $urlField, ?string $digestField = null, string $digestAlgorithm = 'sha256')` 从 JSON 顶级字段创建匿名派生资源；`signedResource()` 使用相同参数创建需要来源通道重新签名的资源。

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Utils;
use We\Common\Request;

$resource = $response->signedResource('download_url', 'hash_value');
$download = $client->call(
    Request::get($resource)->downloadTo(Utils::streamFor('')),
);
```

普通微信通道只接受匿名派生资源，微信支付和支付宝 REST 同时支持匿名与重新签名资源，支付宝 Gateway 不接受派生资源。资源绑定来源通道并强制 HTTPS；微信支付和支付宝 REST 显式拒绝额外查询参数，普通微信通道只发送资源自身 URL。派生资源不能切换到业务调用身份。

## 扩展接口

| 契约 | 实现责任 | 内置实现 |
| --- | --- | --- |
| `We\Common\Transport\HttpTransportInterface` | 按原样同步发送一次最终 PSR-7 请求，不跟随重定向 | `We\Common\Transport\GuzzleTransport` |
| `We\Common\Provider\SigningKeyProviderInterface` | 返回密钥 ID，并对原始报文字节生成 Base64 RSA 签名 | `We\Common\Provider\PemSigningKeyProvider` |
| `We\Common\Provider\TrustMaterialProviderInterface` | 按通道和密钥 ID 返回 PEM 验签公钥 | `We\Common\Provider\StaticTrustMaterialProvider` |
| `We\Common\Protocol\ExternalResourcePolicyInterface` | 在派生资源发送前校验网络目标 | `We\Common\Protocol\PublicNetworkResourcePolicy` |
| `We\Wechat\Common\StoreCacheInterface` | 保存微信 Token、TTL，并提供刷新互斥 | `We\Wechat\Common\FileCacheStore`、`We\Wechat\Common\PsrSimpleCacheStore`、`We\Wechat\Common\NullCacheStore` |
| `We\Wechat\WxOpen\StoreTokenInterface` | 读取 refresh Token，并持久化验证后的授权方刷新响应 | 无默认持久化实现 |
| `We\Wechat\WxOpen\ComponentTicketProviderInterface` | 返回当前有效的 `component_verify_ticket` | `We\Wechat\WxOpen\StaticComponentTicketProvider` |
| `We\Alipay\Common\TokenProviderInterface` | 按身份类型和凭证 ID 返回当前有效 Token | `We\Alipay\Common\StaticTokenProvider` |

Provider 返回空值、非法 Token、非 Base64 签名、非法密钥 ID、未知信任材料或缺失部署依赖时必须失败关闭。公共请求不接受 Guzzle 选项，业务 API 不增加具名方法。
