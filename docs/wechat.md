# 微信平台调用

微信公众号、小程序和微信开放平台使用同一个 `Request`，区别只在配置与默认 Token 身份。

## 微信公众号

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\WeChatClient;
use We\Wechat\WeChatConfig;

$client = WeChatClient::mk(new WeChatConfig('wx_appid', 'app_secret'));

$users = $client
    ->call(Request::get('cgi-bin/user/get')->query(['next_openid' => '']))
    ->json();

$result = $client->call(
    Request::post('cgi-bin/example')->json(['name' => 'demo']),
)->json();
```

默认身份自动获取并注入 access Token。无需 Token 的官方端点使用 `anonymous()`。

## 小程序

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\Wechat\WxAppConfig;
use We\WxAppClient;

$client = WxAppClient::mk(new WxAppConfig('wx_appid', 'app_secret'));
$session = $client
    ->call(Request::get('sns/jscode2session')->anonymous()->query([
        'appid' => 'wx_appid',
        'secret' => 'app_secret',
        'js_code' => 'code',
        'grant_type' => 'authorization_code',
    ]))
    ->json();
```

SDK 不决定哪些端点匿名；调用方根据官方协议显式选择。

## Multipart 与二进制

```php
<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Utils;
use We\Common\MultipartPart;
use We\Common\Request;

$response = $client->call(
    Request::post('cgi-bin/media/upload')
        ->query(['type' => 'image'])
        ->multipart(new MultipartPart(
            'media',
            Utils::streamFor($imageBytes),
            'image.jpg',
            'image/jpeg',
        )),
);

$media = $response->json();
```

成功二进制/失败 JSON 场景使用 `downloadTo()`。SDK 先识别 JSON 错误，再写入目标流。

## 微信开放平台

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\Common\Runtime;
use We\WxOpenClient;
use We\Wechat\WxOpen\StaticComponentTicketProvider;
use We\Wechat\WxOpenConfig;

$runtime = new Runtime(
    authorizers: $authorizerStore,
    componentTickets: new StaticComponentTicketProvider([
        'wx_component_appid' => 'component_verify_ticket',
    ]),
);
$openClient = WxOpenClient::mk(
    new WxOpenConfig('wx_component_appid', 'component_secret'),
    $runtime,
);

$data = $openClient
    ->call(Request::get('cgi-bin/user/get')->asWechatAuthorizer('authorizer_appid'))
    ->json();
```

默认身份使用 component Token；`anonymous()` 不注入 Token；`asWechatAuthorizer()` 使用授权方 Token。component ticket 通过 `ComponentTicketProviderInterface` 注入，授权方 refresh Token 和已验证的刷新响应通过 `StoreTokenInterface` 读写。

微信公众号、小程序和微信开放平台的 `access_token` 查询参数由通道独占。调用方直接写入该参数会在发送前失败。

## 边界

消息回调验签、安全模式加解密、网页授权 URL 和业务端点模型不属于出站 API 调用层。
