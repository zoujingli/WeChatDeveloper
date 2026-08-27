# WeChatDeveloper

WeChatDeveloper 2.0 是微信与支付宝平台 API 的轻量 PHP 调用层，根命名空间为 `We`。

业务应用提供官方路径或方法与请求体；SDK 负责配置校验、Token、HTTP 编码、请求签名、响应验签、错误识别和安全流处理。订单模型、业务 API 封装、通知处理、授权页面、消息加解密、幂等、持久化和状态机由应用负责。

## 支持通道

| 场景 | 通道 | Client | 配置 |
| --- | --- | --- | --- |
| 微信公众号 | `wechat.platform` | `We\WeChatClient` | `We\Wechat\WeChatConfig` |
| 微信小程序 | `wechat.wxapp` | `We\WxAppClient` | `We\Wechat\WxAppConfig` |
| 微信开放平台 | `wechat.service` | `We\WxOpenClient` | `We\Wechat\WxOpenConfig` |
| 微信支付 | `wechat.payment` | `We\WxPayClient` | `We\Wechat\WxPayConfig` |
| 支付宝支付 v2 | `alipay.gateway` | `We\AliPayClient` | `We\Alipay\AliPayConfig` |
| 支付宝 REST v3 | `alipay.rest` | `We\AliRestClient` | `We\Alipay\AliRestConfig` |

“支持平台 API”表示覆盖这些协议的请求形态，不为退款、用户资料等业务端点增加具名方法。

## 环境

- PHP `>= 8.1`
- `ext-json`、`ext-openssl`、`ext-simplexml`
- `guzzlehttp/guzzle ^7.15.3`
- `psr/http-message ^1.1 || ^2.0`
- `psr/simple-cache ^3.0`

```bash
composer require zoujingli/wechat-developer:^2.0
```

## 快速开始

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\WeChatClient;
use We\Wechat\WeChatConfig;

$client = WeChatClient::mk(new WeChatConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$users = $client
    ->call(Request::get('cgi-bin/user/get')->query(['next_openid' => '']))
    ->json();
```

JSON POST 使用同一条链路：

```php
<?php

declare(strict_types=1);

use We\Common\Request;

$response = $client->call(
    Request::post('cgi-bin/message/custom/send')->json([
        'touser' => 'openid',
        'msgtype' => 'text',
        'text' => ['content' => 'hello'],
    ]),
);

$data = $response->json();
```

## 调用模型

公开主路径只有三个概念：

```text
对应通道 *Client::mk(Config) -> call(Request) -> Response
```

- `We\Common\Request`：HTTP 方法、相对目标、查询参数、请求头和唯一请求体；按需附加调用身份、超时或下载目标。
- 通道 Client：各自绑定一种配置，完成对应协议的认证、编码、签名、传输与协议校验。
- `We\Common\Response`：读取 `json()`、`xml()`、`raw()`、状态、响应头、请求 ID 和下载摘要。

请求体支持空请求体、JSON、表单、原始字符串、PSR-7 流和 multipart。响应结构由通道自动识别，不需要结果模式类。

## 安全默认值

- API 目标只能是没有查询参数和片段的相对路径，或支付宝 Gateway 方法。
- Token、`Host`、`Content-Length`、`Content-Type` 和平台签名请求头由通道独占。
- 请求签名使用实际发送字节；支付响应在解析前验签。
- 派生资源只能从可信 `Response` 创建，并校验通道、HTTPS 与网络地址策略。
- 下载先暂存并完成错误识别、验签和摘要校验，再复制到目标流。
- 已发送的业务请求不会被 SDK 隐式重放。

## 文档

- [文档中心](docs/index.md)
- [公开 API](docs/api.md)
- [配置](docs/configuration.md)
- [缓存](docs/cache.md)
- [微信平台](docs/wechat.md)
- [微信支付](docs/payments.md)
- [支付宝](docs/alipay.md)
- [异常](docs/exceptions.md)
- [测试](docs/testing.md)
- [设计](docs/design.md)
- [迁移指南](docs/migration-2.0.md)
- [写作与注释规范](docs/style-guide.md)
- [领域词汇](CONTEXT.md)
- [架构决策](docs/adr/)
- [协议事实矩阵](docs/research/platform-call-scenarios.md)
- [贡献指南](CONTRIBUTING.md)
- [安全策略](SECURITY.md)
- [更新记录](CHANGELOG.md)

## 许可证

[MIT](LICENSE)
