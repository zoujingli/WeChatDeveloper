# WeChatDeveloper

WeChatDeveloper 2.0 是面向微信与支付宝官方 API 的轻量 PHP SDK，根命名空间为 `We`。

SDK 负责配置校验、访问令牌缓存、HTTP 调用、请求签名、平台响应验签、通知验签与解密，以及统一异常。业务系统继续负责订单、幂等、持久化和业务状态机。

2.0 是一次有意的主版本重写：使用配置对象、带类型的客户端工厂和“官方 path + 参数数组”调用模型，不恢复 1.x 的大量业务接口类，也不提供运行时兼容层。

## 支持边界

| 平台 | 能力 | 不包含 |
|------|------|--------|
| 微信公众平台 | access token、JSON API、网页授权、消息加解密、媒体下载/上传 | 模拟 `mp.weixin.qq.com` 后台 |
| 微信小程序 | access token、JSON API、登录等无 token 调用、文件下载/上传 | 小程序业务模型 |
| 微信服务平台 | component token、授权页、授权方调用、Token 存储接缝 | 业务账号仓库 |
| 微信支付 APIv3 | 商户请求签名、平台响应验签、通知验签解密、账单下载 | 订单幂等、支付状态机 |
| 支付宝开放平台 | RSA/RSA2 请求签名、同步响应验签、通知验签、授权 | 商家中心网页自动化 |
| 支付宝支付 | 电脑网站支付、退款和通用网关调用 | 业务订单存储 |

官方新增接口只要沿用已有协议形态，通常可直接传官方 path 和参数调用，无需等待 SDK 增加别名方法。

## 环境要求

- PHP `>= 8.1`
- `ext-json`
- `ext-openssl`
- `ext-simplexml`
- `guzzlehttp/guzzle ^7.15.3`
- `psr/simple-cache ^3.0`

CI 覆盖 PHP 8.1 至 8.4，并验证最低依赖组合。

## 安装

稳定版发布后：

```bash
composer require zoujingli/wechat-developer:^2.0
```

跟踪 2.0 开发分支：

```bash
composer require zoujingli/wechat-developer:2.0.x-dev
```

## 快速开始

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatPlatformConfig;

$client = new Client();
$platform = $client->wechatPlatform(new WechatPlatformConfig(
    appid: 'wx_appid',
    appSecret: 'app_secret',
));

$users = $platform->get('cgi-bin/user/get', [
    'next_openid' => '',
]);
```

微信普通接口会自动获取并附加 access token。path 使用官方相对路径，不要传绝对 URL。

POST 参数默认编码为 JSON：

```php
<?php

declare(strict_types=1);

$menu = $platform->post('cgi-bin/menu/create', [
    'button' => [
        [
            'type' => 'click',
            'name' => '今日推荐',
            'key' => 'TODAY',
        ],
    ],
]);
```

## 类型工厂

`We\Client` 显式声明六个工厂方法，IDE 和静态分析可直接获知返回类型：

| 工厂 | 配置 | 返回客户端 |
|------|------|------------|
| `wechatPlatform()` | `WechatPlatformConfig` | 微信公众平台 |
| `wechatWxapp()` | `WechatWxappConfig` | 微信小程序 |
| `wechatService()` | `WechatServiceConfig` | 微信服务平台 |
| `wechatPayment()` | `WechatPaymentConfig` | 微信支付 APIv3 |
| `alipayPlatform()` | `AlipayPlatformConfig` | 支付宝开放平台 |
| `alipayPayment()` | `AlipayPaymentConfig` | 支付宝支付 |

配置驱动场景可保留动态入口：

```php
<?php

declare(strict_types=1);

use We\Client;
use We\Config\WechatWxappConfig;

$client = new Client();
$wxapp = $client->get(
    'wechat.wxapp',
    new WechatWxappConfig('wx_appid', 'app_secret'),
);
```

支持的动态通道是 `wechat.platform`、`wechat.wxapp`、`wechat.service`、`wechat.payment`、`alipay.platform` 和 `alipay.payment`。2.0 不再使用 `__call()` 推断工厂名称。

## 安全默认值

- 配置对象构造后保持只读；`fromArray()` 的字符串字段拒绝数组、布尔值等隐式类型转换。
- 支付宝应用私钥与支付宝公钥均为必填项；同步响应与通知必须验签。
- 微信支付必须配置平台公钥或平台证书及对应序列号；普通 JSON 响应在解析前验签。
- 微信支付通知默认只接受时间戳偏差不超过 300 秒的已签名原始 body；配置为 `0` 才会关闭时间检查。
- 微信账单使用 `downloadBill()` 完成“申请下载地址 + HTTPS 文件下载”两阶段流程。
- 支付密钥必须是 RSA；可解析但算法错误的 EC 密钥会被拒绝。
- 通用平台客户端只接受相对 path，账单专用下载器也只接受已验签响应中的 HTTPS 地址。

## 文档

从[文档中心](docs/index.md)按接入目标阅读，或直接查阅：

- 入门与参考：[配置与凭证](docs/configuration.md)、[公开 API 速查](docs/api.md)、[缓存](docs/cache.md)、[异常](docs/exceptions.md)。
- 平台能力：[微信平台](docs/wechat.md)、[微信支付](docs/payments.md)、[支付宝](docs/alipay.md)。
- 维护与升级：[设计](docs/design.md)、[测试与贡献](docs/testing.md)、[从 1.x 迁移到 2.0](docs/migration-2.0.md)、[更新记录](CHANGELOG.md)。

## 错误处理

所有 SDK 故障都继承 `SdkException`；平台异常仍可精确捕获：

```php
<?php

declare(strict_types=1);

use We\Exception\SdkException;
use We\Exception\SignatureException;

try {
    $result = $payment->get('v3/pay/transactions/id/4200000000000000000000000000', [
        'mchid' => '1900000001',
    ]);
} catch (SignatureException $exception) {
    report($exception);
} catch (SdkException $exception) {
    report($exception);
}
```

## 版本策略

2.0 保持 PHP 8.1 最低版本。稳定发布后，1.x 的必要维护应留在独立维护分支；2.0 不通过兼容层承载旧命名空间或旧业务类。

标签发布只有在 Composer 严格校验、代码风格、PHPStan、PHP 8.1 至 8.4 测试矩阵和最低依赖测试全部通过后才创建 GitHub Release。含 `-alpha`、`-beta` 或 `-rc` 的标签会标记为预发布。

## 许可证

[MIT](LICENSE)
