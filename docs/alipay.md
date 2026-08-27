# 支付宝

支付宝支付 v2 与 REST v3 使用独立 Client 和配置，共享 `call(Request)->Response` 与公共运行依赖。

## 支付宝支付 v2

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\AliPayClient;

$gateway = AliPayClient::mk($aliPayConfig);
$trade = $gateway->call(
    Request::post('alipay.trade.query')->json([
        'out_trade_no' => 'ORDER-1',
    ]),
)->json();
```

通道生成 AOP 公共参数、`biz_content` 和签名，并只返回通过节点级验签的业务响应。配置 `format: XML` 时，通道按 `{method}_response` 或 `error_response` 验签，`Response::xml()` 保留重复节点。

普通字段与文件可用 `form()` 或 `multipart()`。Gateway 传输只支持 GET/POST；GET 不接受 `multipart`，避免文件部件被静默丢弃。

官方明确不签名的媒体响应必须显式标记：

```php
<?php

declare(strict_types=1);

use We\Common\Request;

$bytes = $gateway
    ->call(Request::get('alipay.mobile.public.multimedia.download')->rawMedia())
    ->raw();
```

`rawMedia()` 不能用于其他通道，也不会关闭 HTTP 状态码或 JSON/XML 平台错误识别。配合 `downloadTo()` 时，结构化错误在目标流写入前处理。

## 支付宝 REST v3

```php
<?php

declare(strict_types=1);

use We\Alipay\Common\StaticTokenProvider;
use We\Alipay\Common\TokenKind;
use We\Common\Request;
use We\Common\Runtime;
use We\AliRestClient;

$tokenProvider = new StaticTokenProvider([
    TokenKind::AlipayApp->value => ['credential-id' => 'app-auth-token'],
]);
$rest = AliRestClient::mk($aliRestConfig, new Runtime(tokens: $tokenProvider));
$resource = $rest->call(
    Request::post('v3/example/resources')
        ->query(['expand' => 'detail'])
        ->json(['name' => 'demo'])
        ->asAlipayApp('credential-id'),
)->json();
```

通道对最终 HTTP 方法、URI、协议规定的报文字节和应用授权 Token 生成签名，并强制验证响应头。

REST v3 的 `multipart` 请求只允许一个普通 `data` 部件；该部件的原始字符串参与签名，文件部件继续进入 HTTP 请求体但不进入签名正文。

## 调用身份

Gateway 与 REST 都支持 `asAlipayUser()` 和 `asAlipayApp()`。前者通过 `TokenProviderInterface` 解析用户 `auth_token`；后者解析代调用应用 Token，Gateway 写入 `app_auth_token`，REST 写入 `alipay-app-auth-token` 请求头。

默认身份与 `anonymous()` 都不会注入这两类可选 Token，但应用级请求签名始终保留。REST 不接受调用方直接提供 `auth_token`；Gateway 的 AOP 公共参数同样由通道生成。请求组合在读取 Token Provider 前完成校验，Provider 返回的空值或非法请求头值失败关闭。

`StaticTokenProvider` 的第一层键使用 `TokenKind::AlipayUser->value` 或 `TokenKind::AlipayApp->value`，第二层键是传给身份方法的凭证 ID。生产系统可以实现 `TokenProviderInterface`，在 `token()` 内完成过期检查或刷新。

## 边界

异步通知、授权页面、HTML form、APP order string 和 AES 业务字段处理不属于出站 API 调用层。
