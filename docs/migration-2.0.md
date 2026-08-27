# 迁移到 2.0

2.0 要求 PHP 8.1，并采用六个独立通道 Client，不提供兼容层，包括旧类名、旧命名空间和配置别名。

## 最终入口

```php
<?php

declare(strict_types=1);

use We\Common\Request;
use We\WeChatClient;
use We\Wechat\WeChatConfig;

$client = WeChatClient::mk(new WeChatConfig('wx_appid', 'app_secret'));
$data = $client->call(Request::get('cgi-bin/user/get'))->json();
```

| 场景 | 最终 Client | 最终 Config |
| --- | --- | --- |
| 微信公众号 | `We\WeChatClient` | `We\Wechat\WeChatConfig` |
| 微信小程序 | `We\WxAppClient` | `We\Wechat\WxAppConfig` |
| 微信开放平台 | `We\WxOpenClient` | `We\Wechat\WxOpenConfig` |
| 微信支付 | `We\WxPayClient` | `We\Wechat\WxPayConfig` |
| 支付宝支付 v2 | `We\AliPayClient` | `We\Alipay\AliPayConfig` |
| 支付宝 REST v3 | `We\AliRestClient` | `We\Alipay\AliRestConfig` |

## 入口映射

| 旧入口或早期 2.0 入口 | 最终入口 |
| --- | --- |
| `get()/post()/call()` | `call(Request)` |
| `raw()` | `Response::raw()` |
| `upload()` | `Request::multipart()` |
| `download()` | `Request::downloadTo()` |
| 根配置分派 | 对应场景的 `*Client::mk(Config)` |
| `We\Platform\Wechat\*Client` / `We\Platform\Alipay\*Client` | 对应的六个最终 Client |
| `We\PlatformClient` | `We\WeChatClient` |
| `We\WxappClient` | `We\WxAppClient` |
| `We\ServiceClient` | `We\WxOpenClient` |
| `We\PaymentClient` | `We\WxPayClient` |
| `We\GatewayClient` | `We\AliPayClient` |
| `We\RestClient` | `We\AliRestClient` |
| `We\Request`、`We\Response`、`We\Resource`、`We\Runtime`、`We\MultipartPart` | 对应的 `We\Common\*` 类型 |
| `send(Call)` 与 Result 类族 | `call(Request)->json()/xml()/raw()` |

业务端点快捷方法不再保留。退款、账号资料、下单等全部使用官方路径或方法与请求体。

## 配置映射

| 旧配置或早期 2.0 配置 | 最终配置 |
| --- | --- |
| `We\Config\WechatPlatformConfig` / `We\Wechat\Platform\WechatPlatformConfig` / `We\Wechat\OfficialAccount\WeChatConfig` / `We\Wechat\WeChat\WeChatConfig` | `We\Wechat\WeChatConfig` |
| `We\Config\WechatWxappConfig` / `We\Wechat\Wxapp\WechatWxappConfig` / `We\Wechat\MiniProgram\WxAppConfig` / `We\Wechat\WxApp\WxAppConfig` | `We\Wechat\WxAppConfig` |
| `We\Config\WechatServiceConfig` / `We\Wechat\Service\WechatServiceConfig` / `We\Wechat\OpenPlatform\WxOpenConfig` / `We\Wechat\WxOpen\WxOpenConfig` | `We\Wechat\WxOpenConfig` |
| `We\Config\WechatPaymentConfig` / `We\Wechat\Payment\WechatPaymentConfig` / `We\Wechat\Pay\WxPayConfig` / `We\Wechat\WxPay\WxPayConfig` | `We\Wechat\WxPayConfig` |
| `We\Config\AlipayPlatformConfig` / `We\Alipay\Gateway\AlipayGatewayConfig` / `We\Alipay\Pay\AliPayConfig` / `We\Alipay\AliPay\AliPayConfig` | `We\Alipay\AliPayConfig` |
| `We\Alipay\AliRest\AliRestConfig` | `We\Alipay\AliRestConfig` |
| `We\Config\AlipayPaymentConfig` / `We\Alipay\Rest\AlipayRestConfig` / `We\Alipay\Rest\AliRestConfig` | 按实际协议迁移到 `AliPayConfig` 或 `AliRestConfig` |
| `We\Config\WechatTokenStrategy` | `We\Wechat\Common\WeChatTokenStrategy` |
| `We\Common\Config\EndpointProfile` | `We\Common\Config\Endpoint` |

`Endpoint` 只保留 HTTPS `baseUri`；`endpoint_profile` 和未参与调用的环境名称已删除。

`cert_public` 等旧模糊字段迁移为明确的 `platform_public_key` / `platform_certificate` 或 `alipay_public_key`。

## 扩展接口映射

| 旧接口 | 最终接口 |
| --- | --- |
| `We\Contract\ConfigInterface` | 删除；六个 Client 直接接受具体配置类 |
| `We\Contract\StoreCacheInterface` | `We\Wechat\Common\StoreCacheInterface` |
| `We\Contract\StoreTokenInterface` / `We\Wechat\Service\StoreTokenInterface` / `We\Wechat\OpenPlatform\StoreTokenInterface` | `We\Wechat\WxOpen\StoreTokenInterface` |
| 微信开放平台 ticket Provider | `We\Wechat\WxOpen\ComponentTicketProviderInterface` |
| 支付宝 Token Provider | `We\Alipay\Common\TokenProviderInterface` |
| 跨生态签名、信任材料和异常类型 | `We\Common\Provider\*` 或 `We\Common\Exception\*` |

## 响应与异常

数组结果迁移为 `Response::json()`，XML 使用 `xml()`，原始字节使用 `raw()`。下载字节数和摘要从同一 `Response` 读取。

平台平行异常树迁移为 `We\Common\Exception` 下的阶段异常；支付验签失败统一捕获 `We\Common\Exception\SignatureException`。微信 Token 缓存继续实现 `We\Wechat\Common\StoreCacheInterface`。

## 移除范围

2.0 调用层不提供消息/通知、授权 URL、客户端调起和字段加解密能力。已有业务应将这些流程留在应用服务或专用协议模块中。

查询参数由 SDK 按 RFC 3986 编码。缓存键各段还会把 `.` 编码为 `%2E`，避免与段分隔符冲突。公共请求不接受 Guzzle 选项，绝对 URL 只能来自可信响应派生资源。
