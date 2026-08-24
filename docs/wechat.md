# 微信平台

微信公众平台、小程序和服务平台客户端共享“官方相对 path + 参数数组”的调用方式。SDK 管理协议和 token，不把官方接口复制成大量业务方法。

## 公众平台

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$platform = (new Client())->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$users = $platform->get('cgi-bin/user/get', [
    'next_openid' => '',
]);

$result = $platform->post('cgi-bin/message/custom/send', [
    'touser' => 'openid',
    'msgtype' => 'text',
    'text' => ['content' => 'hello'],
]);
```

GET 的第二个参数作为 query；POST 的第二个参数默认作为 JSON body。`options` 可透传 Guzzle 的 `headers`、`timeout`、`query`、`body` 或 `form_params`。

网页授权等不需要公众号 access token 的接口应显式关闭 token：

```php
<?php

declare(strict_types=1);

$oauth = $platform->get('sns/oauth2/access_token', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'code' => 'authorization_code',
    'grant_type' => 'authorization_code',
], [
    'with_token' => false,
]);
```

## 网页授权与扫码登录地址

公众号网页授权和网站应用扫码登录使用 `open.weixin.qq.com`，不会向普通 JSON API 发起请求。传入对应特殊 path 时，客户端返回包含 URL 的数组；`appid` 默认取自当前配置，参数数组显式提供 `appid` 时以参数值为准：

```php
<?php

declare(strict_types=1);

$authorization = $platform->get('connect/oauth2/authorize', [
    'redirect_uri' => 'https://example.com/wechat/oauth/callback',
    'scope' => 'snsapi_userinfo',
    'state' => 'csrf-state',
]);
$authorizationUrl = (string) $authorization['url'];

$qrconnect = $platform->get('connect/qrconnect', [
    'redirect_uri' => 'https://example.com/wechat/qr/callback',
    'scope' => 'snsapi_login',
    'state' => 'csrf-state',
]);
$qrconnectUrl = (string) $qrconnect['url'];
```

调用方负责生成并验证 `state`，以及在回调阶段校验重定向上下文。生成 URL 本身不获取 access token。

## 小程序

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatWxappConfig;

$wxapp = (new Client())->wechatWxapp(new WechatWxappConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$session = $wxapp->get('sns/jscode2session', [
    'appid' => 'wx_appid',
    'secret' => 'app_secret',
    'js_code' => 'login_code',
    'grant_type' => 'authorization_code',
], [
    'with_token' => false,
]);
```

## 原始响应、下载和上传

`raw()` 与 `download()` 返回 PSR-7 `ResponseInterface`，适用于图片、媒体和其他非 JSON 数据。`upload()` 接受 Guzzle multipart 结构并解析平台 JSON 响应。

```php
<?php

declare(strict_types=1);

$image = $platform->download('cgi-bin/media/get', [
    'media_id' => 'MEDIA_ID',
]);
$binary = (string) $image->getBody();

$upload = $platform->upload('cgi-bin/media/upload', [
    [
        'name' => 'media',
        'contents' => fopen(__DIR__ . '/demo.jpg', 'rb'),
        'filename' => 'demo.jpg',
    ],
], [
    'type' => 'image',
]);
```

所有通用微信平台 path 必须是相对路径。`https://...`、`http://...` 和 `//host/path` 都会被拒绝，避免把平台客户端放大为任意 URL 请求器。

## 消息安全模式

公众平台配置 `token` 和 `encodingAesKey` 后，可通过特殊操作名处理消息：

```php
<?php

declare(strict_types=1);

$plain = $platform->post('decrypt_message', [
    'body' => $rawXml,
    'msg_signature' => $messageSignature,
    'timestamp' => $timestamp,
    'nonce' => $nonce,
]);

$encrypted = $platform->post('encrypt_message', [
    'body' => $replyXml,
    'timestamp' => (string) time(),
    'nonce' => $nonce,
]);
```

## 微信服务平台

服务平台客户端管理 component access token，并可代表授权方调用官方接口。授权方 refresh token 的持久化由业务系统实现 `StoreTokenInterface`。

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatServiceConfig;

$client = new Client(authorizers: $authorizerTokenStore);
$service = $client->wechatService(new WechatServiceConfig(
    componentAppid: 'wx_component_appid',
    componentAppSecret: 'component_secret',
    componentToken: 'componentToken123',
    componentEncodingAesKey: 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG',
));

$componentToken = $service->componentAccessToken($componentVerifyTicket);
$preAuth = $service->createPreAuthCode($componentToken);
$url = $service->authorizationUrl(
    (string) $preAuth['pre_auth_code'],
    'https://example.com/wechat/component/callback',
);

$authorization = $service->queryAuth(
    $componentToken,
    'authorization_code_from_callback',
);
$authorizerAppid = (string) $authorization['authorization_info']['authorizer_appid'];

$authorizer = $service->authorizerInfo($componentToken, $authorizerAppid);
```

通过通用 `get()`/`post()` 代表授权方调用时，在 options 中传 `authorizer_appid` 与 `component_access_token`。SDK 从 `StoreTokenInterface` 读取 refresh token，刷新后把完整 payload 回写业务存储。

```php
<?php

declare(strict_types=1);

$users = $service->get('cgi-bin/user/get', [
    'next_openid' => '',
], [
    'authorizer_appid' => $authorizerAppid,
    'component_access_token' => $componentToken,
]);
```

也可以用 `requestAsAuthorizer()` 显式表达同一调用。两种入口都要求根 `Client` 已注入 `StoreTokenInterface`；未注入仓库、refresh token 为空或刷新响应无效时会抛出 `WechatException`。

刷新响应会先校验 `authorizer_access_token` 与有效期，再回写 `StoreTokenInterface`。无效响应不会进入业务存储或 token 缓存。

## 返回与错误

JSON 调用成功时返回数组。微信配置和协议错误抛出 `WechatException`，平台业务错误或无效 JSON 抛出 `ApiException`，网络传输错误抛出 `TransportException`，签名错误抛出 `SignatureException`。它们都可由 `SdkException` 统一捕获。
