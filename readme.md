# WeChatDeveloper for PHP

[![star](https://gitcode.com/ThinkAdmin/WeChatDeveloper/star/badge.svg)](https://gitcode.com/ThinkAdmin/ThinkAdmin)
[![star](https://gitee.com/zoujingli/WeChatDeveloper/badge/star.svg?theme=gvp)](https://gitee.com/zoujingli/WeChatDeveloper)
[![Latest Stable Version](https://poser.pugx.org/zoujingli/wechat-developer/v/stable)](https://packagist.org/packages/zoujingli/wechat-developer)
[![Total Downloads](https://poser.pugx.org/zoujingli/wechat-developer/downloads)](https://packagist.org/packages/zoujingli/wechat-developer)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/wechat-developer/d/monthly)](https://packagist.org/packages/zoujingli/wechat-developer)
[![Daily Downloads](https://poser.pugx.org/zoujingli/wechat-developer/d/daily)](https://packagist.org/packages/zoujingli/wechat-developer)
[![PHP Version Require](http://poser.pugx.org/zoujingli/wechat-developer/require/php)](https://packagist.org/packages/wechat-developer)
[![License](https://poser.pugx.org/zoujingli/wechat-developer/license)](https://packagist.org/packages/zoujingli/wechat-developer)

## 🚀 项目简介

**WeChatDeveloper** 是一个功能全面、安全可靠的 PHP 微信和支付宝开发 SDK，基于 [wechat-php-sdk](https://github.com/zoujingli/wechat-php-sdk) 重构优化而成。项目经过全面的安全加固和代码质量提升，为开发者提供稳定、安全、易用的微信生态和支付解决方案。

## ✨ 核心特性

### 🔒 安全可靠

- **输入验证**：全面防护 XSS 攻击，所有用户输入都经过严格过滤和验证
- **文件安全**：文件操作前进行存在性和权限检查，防止恶意利用
- **加密安全**：使用 SHA-256 替代 MD5，提供更强的安全防护
- **序列化安全**：添加反序列化数据验证，防止代码执行攻击

### 🎯 功能全面

- **微信生态**：支持公众号、小程序、企业微信全生态开发
- **支付功能**：支持微信支付 V2/V3、支付宝支付全场景
- **多端支持**：覆盖 App、H5、PC、小程序等所有平台
- **接口完整**：涵盖用户管理、消息推送、素材管理、支付等所有核心功能

### ⚡ 性能优化

- **自动刷新**：AccessToken 失效自动刷新机制
- **缓存支持**：支持自定义缓存驱动，可扩展 Redis 等
- **错误重试**：智能错误重试机制，提高接口调用成功率
- **类型安全**：修复所有类型声明问题，提升代码质量
- **自动缓存清理**：自动清理 CURL 临时缓存文件，适配常驻内存框架
- **通用接口支持**：提供 `callApi()` 万能接口，支持多种 HTTP 方法，适用于所有场景

### 🛠 易于使用

- **统一入口**：通过`\We::`静态方法统一创建各种功能实例
- **向后兼容**：完全保持原有 API 和参数，无需修改现有代码
- **通用接口**：提供标准化的 `callApi()` 方法，支持 GET/POST/PUT/DELETE 等多种 HTTP 方法
- **文档完善**：提供详细的使用文档和示例代码
- **社区支持**：活跃的社区和持续的技术支持

## 📋 系统要求

- **PHP 版本**：最低要求 PHP 5.4，建议 PHP 7.0+ 以获取最佳性能
- **扩展要求**：curl、json、xml、openssl、mbstring、bcmath
- **权限要求**：缓存目录需要写权限
- **推荐环境**：PHP 7.4+ / PHP 8.0+ 生产环境

## 📦 快速开始

### 安装方式

#### 方式一：Composer 安装（推荐）

```bash
# 安装稳定版本
composer require zoujingli/wechat-developer

# 安装开发版本
composer require zoujingli/wechat-developer dev-master

# 更新到最新版本
composer update zoujingli/wechat-developer
```

#### 方式二：直接下载

```bash
# 下载项目到本地
git clone https://github.com/zoujingli/WeChatDeveloper.git

# 在项目中引入
include "WeChatDeveloper/include.php";
```

### 基础使用

```php
<?php
// 1. 引入SDK
include "WeChatDeveloper/include.php";

// 2. 配置参数
$config = [
    'appid'     => 'your_wechat_appid',
    'appsecret' => 'your_wechat_appsecret',
    'mch_id'    => 'your_merchant_id',      // 微信支付需要
    'mch_key'   => 'your_merchant_key',     // 微信支付需要
    'cache_path' => '/path/to/cache'        // 可选，缓存目录
];

// 3. 创建实例并调用
try {
    // 微信用户管理
    $user = \We::WeChatUser($config);
    $userList = $user->getUserList();

    // 微信支付
    $pay = \We::WePayOrder($config);
    $order = $pay->create($orderData);

    // 支付宝支付
    $alipay = \We::AliPayWeb($config);
    $html = $alipay->apply($payData);

    // 使用通用接口（万能接口）
    // 所有接口类都提供 callApi() 方法，支持多种 HTTP 方法
    $result = $user->callApi('https://api.weixin.qq.com/cgi-bin/user/get?ACCESS_TOKEN', [], 'GET');
    $result = $pay->callApi('https://api.mch.weixin.qq.com/pay/unifiedorder', $data, 'POST');
    $result = $alipay->callApi('alipay.trade.query', $params, 'GET');  // 支付宝：apiMethod 作为第一参数

} catch (Exception $e) {
    echo "错误：" . $e->getMessage();
}
```

## 🎯 功能模块

### 📱 微信生态支持

#### 微信公众号

- **用户管理**：用户信息获取、标签管理、分组管理
- **消息推送**：模板消息、客服消息、群发消息
- **素材管理**：图片、语音、视频、图文素材上传和管理
- **菜单管理**：自定义菜单创建、查询、删除
- **网页授权**：OAuth2.0 网页授权，获取用户信息
- **二维码**：临时二维码、永久二维码生成
- **JSSDK**：微信前端 JS-SDK 支持
- **卡券功能**：微信卡券接口支持
- **门店管理**：门店 WIFI 管理、摇一摇周边

#### 微信小程序

- **数据加密**：小程序数据加密解密处理
- **用户管理**：用户信息获取、登录状态管理
- **消息推送**：订阅消息、模板消息、动态消息
- **二维码**：小程序码生成、URL Scheme
- **内容安全**：图片内容检测、文本内容检测
- **物流助手**：发货信息管理、物流状态查询
- **直播功能**：小程序直播接口支持
- **搜索优化**：小程序页面搜索优化
- **插件管理**：小程序插件申请、管理
- **OCR 服务**：身份证、银行卡、驾驶证识别
- **生物认证**：指纹、面部识别支持

#### 企业微信

- **部门管理**：部门信息获取、创建、更新
- **用户管理**：企业用户信息管理
- **消息推送**：企业消息推送功能

### 💰 支付功能支持

#### 微信支付

- **V2 接口**：统一下单、查询、关闭、退款
- **V3 接口**：新一代支付接口，支持更多功能
- **支付方式**：JSAPI、APP、H5、Native、小程序支付
- **订单管理**：订单创建、查询、关闭、退款
- **账单管理**：对账单下载、交易明细查询
- **企业付款**：打款到零钱、打款到银行卡
- **分账功能**：微信分账接口支持
- **代金券**：代金券创建、发放、核销
- **红包功能**：微信红包发送和管理

#### 支付宝支付

- **支付方式**：App 支付、Web 支付、Wap 支付、扫码支付、刷卡支付
- **订单管理**：订单创建、查询、关闭、退款
- **转账功能**：单笔转账、批量转账
- **账单管理**：对账单下载、交易查询
- **证书支持**：RSA、RSA2 签名，证书模式支持

## 💡 使用案例

### 📱 微信公众号功能

#### 用户管理

```php
<?php
// 获取用户列表
$user = \We::WeChatUser($config);
$result = $user->getUserList();

// 批量获取用户信息
foreach (array_chunk($result['data']['openid'], 100) as $openids) {
    $userList = $user->getBatchUserInfo($openids);
    foreach ($userList['user_info_list'] as $userInfo) {
        echo "用户：" . $userInfo['nickname'] . "\n";
    }
}

// 设置用户备注
$user->updateMark('openid', 'VIP用户');
```

#### 二维码生成

```php
<?php
// 创建临时二维码
$qrcode = \We::WeChatQrcode($config);
$result = $qrcode->create('场景内容');

// 获取二维码链接
$url = $qrcode->url($result['ticket']);
echo "二维码链接：" . $url;
```

#### 菜单管理

```php
<?php
// 获取当前菜单
$menu = \We::WeChatMenu($config);
$result = $menu->get();

// 创建自定义菜单
$menuData = [
    'button' => [
        [
            'type' => 'click',
            'name' => '今日歌曲',
            'key' => 'V1001_TODAY_MUSIC'
        ],
        [
            'name' => '菜单',
            'sub_button' => [
                [
                    'type' => 'view',
                    'name' => '搜索',
                    'url' => 'http://www.soso.com/'
                ]
            ]
        ]
    ]
];
$menu->create($menuData);
```

### 💰 微信支付功能

#### 微信支付 V2 接口

```php
<?php
// 创建支付订单
$pay = \We::WePayOrder($config);
$options = [
    'body'             => '测试商品',
    'out_trade_no'     => time(),
    'total_fee'        => '1',
    'openid'           => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo',
    'trade_type'       => 'JSAPI', // JSAPI/NATIVE/APP/MWEB
    'notify_url'       => 'https://your-domain.com/notify.php',
    'spbill_create_ip' => '127.0.0.1',
];

$result = $pay->create($options);

// 生成JSAPI支付参数
$jsApiParams = $pay->createParamsForJsApi($result['prepay_id']);
// 将 $jsApiParams 传给前端发起支付
```

#### 微信支付 V3 接口

```php
<?php
// 创建V3支付订单
$payment = \WePayV3\Order::instance($config);
$order = (string)time();

// JSAPI支付
$result = $payment->create('jsapi', [
    'appid'        => $config['appid'],
    'mchid'        => $config['mch_id'],
    'description'  => '商品描述',
    'out_trade_no' => $order,
    'notify_url'   => 'https://your-domain.com/notify.php',
    'payer'        => ['openid' => 'o38gps3vNdCqaggFfrBRCRikwlWY'],
    'amount'       => ['total' => 2, 'currency' => 'CNY'],
]);

// H5支付
$result = $payment->create('h5', [
    'appid'        => $config['appid'],
    'mchid'        => $config['mch_id'],
    'description'  => '商品描述',
    'out_trade_no' => $order,
    'notify_url'   => 'https://your-domain.com/notify.php',
    'amount'       => ['total' => 2, 'currency' => 'CNY'],
    'scene_info'   => [
        'h5_info' => ['type' => 'Wap'],
        'payer_client_ip' => '14.23.150.211',
    ],
]);

// 查询订单
$result = $payment->query($order);

// 创建退款
$refundResult = $payment->createRefund([
    'out_trade_no'  => $order,
    'out_refund_no' => strval(time()),
    'amount'        => [
        'refund'   => 2,
        'total'    => 2,
        'currency' => 'CNY'
    ]
]);
```

#### 微信红包

```php
<?php
// 发送微信红包
$redpack = \We::WePayRedpack($config);
$options = [
    'mch_billno'   => time(),
    're_openid'    => 'o38gps3vNdCqaggFfrBRCRikwlWY',
    'send_name'    => '商户名称',
    'act_name'     => '活动名称',
    'total_amount' => '100',
    'total_num'    => '1',
    'wishing'      => '感谢您参加活动！',
    'remark'       => '快来抢红包！',
    'client_ip'    => '127.0.0.1',
];

$result = $redpack->create($options);

// 查询红包记录
$result = $redpack->query($options['mch_billno']);
```

### 💳 支付宝支付功能

#### 网站支付

```php
<?php
// 支付宝网站支付
$alipay = \We::AliPayWeb($config);
$result = $alipay->apply([
    'out_trade_no' => time(),
    'total_amount' => '1',
    'subject'      => '支付订单描述',
]);

// 直接输出HTML表单，用户点击即可跳转支付
echo $result;
```

#### App 支付

```php
<?php
// 支付宝App支付
$alipay = \We::AliPayApp($config);
$result = $alipay->apply([
    'out_trade_no' => strval(time()),
    'total_amount' => '1',
    'subject'      => '支付宝订单标题',
]);

// 返回支付参数字符串，传给App端
echo $result;
```

#### 转账功能

```php
<?php
// 支付宝转账
$transfer = \We::AliPayTransfer($config);
$result = $transfer->create([
    'out_biz_no'   => time(),
    'trans_amount' => '10',
    'product_code' => 'TRANS_ACCOUNT_NO_PWD',
    'biz_scene'    => 'DIRECT_TRANSFER',
    'payee_info'   => [
        'identity'      => 'zoujingli@qq.com',
        'identity_type' => 'ALIPAY_LOGON_ID',
        'name'          => '收款人姓名',
    ],
]);
```

### 📱 微信小程序功能

#### 用户登录和数据解密

```php
<?php
// 小程序用户登录
$mini = \We::WeMiniCrypt($config);

// 获取session_key
$session = $mini->session($code);

// 解密用户数据
$userInfo = $mini->userInfo($code, $iv, $encryptedData);

// 直接解密数据
$decoded = $mini->decode($iv, $sessionKey, $encryptedData);
```

#### 小程序码生成

```php
<?php
// 生成小程序码
$qrcode = \We::WeMiniQrcode($config);
$result = $qrcode->create([
    'scene' => 'id=123',
    'page'  => 'pages/index/index',
    'width' => 430
]);
```

### 🔧 高级功能

#### 通用接口（万能接口）

所有接口类都提供了 `callApi()` 通用方法，支持多种 HTTP 方法（GET、POST、PUT、DELETE、PATCH、HEAD、OPTIONS），可以直接传入完整 URL 和参数进行请求，适用于官方新增接口或自定义接口调用。

**方法签名：**

```php
// 微信公众号
callApi(string $url, array|string $data = [], string $method = 'GET')

// 微信支付V2
callApi(string $url, array|string $data = [], string $method = 'POST', bool $isCert = false, string $signType = 'HMAC-SHA256')

// 微信支付V3
callApi(string $url, array|string $data = '', string $method = 'POST', bool $verify = false)

// 支付宝
callApi(string $apiMethod, array|string $data = [], string $method = 'GET', bool $verify = false)
```

**支持的 HTTP 方法：**

- `GET` - 获取资源（默认：微信公众号、支付宝）
- `POST` - 创建/提交数据（默认：微信支付）
- `PUT` - 更新资源
- `DELETE` - 删除资源
- `PATCH` - 部分更新
- `HEAD` - 获取响应头
- `OPTIONS` - 获取支持的方法

##### 微信公众号通用接口

```php
<?php
$user = \We::WeChatUser($config);

// GET请求 - 自动处理ACCESS_TOKEN
$result = $user->callApi(
    'https://api.weixin.qq.com/cgi-bin/user/info?openid=OPENID&lang=zh_CN&ACCESS_TOKEN',
    [], // GET参数（如果URL中已包含参数，这里可以为空）
    'GET'
);

// POST请求 - 批量获取用户信息（自动JSON编码）
$result = $user->callApi(
    'https://api.weixin.qq.com/cgi-bin/user/info/batchget?ACCESS_TOKEN',
    ['user_list' => [['openid' => 'xxx'], ['openid' => 'yyy']]],
    'POST'
);

// PUT请求 - 更新资源
$result = $user->callApi($url, $data, 'PUT');

// DELETE请求 - 删除资源
$result = $user->callApi($url, $data, 'DELETE');

// PATCH请求 - 部分更新
$result = $user->callApi($url, $data, 'PATCH');
```

**参数说明：**

- `$url` (string) - 完整URL或相对路径（支持 ACCESS_TOKEN 占位符，自动替换）
- `$data` (array|string) - 请求参数（GET参数或POST数据，支持数组或字符串）
- `$method` (string) - 请求方法 GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS，默认 GET

**默认行为：**

- ✅ 自动处理 ACCESS_TOKEN（URL中包含 ACCESS_TOKEN 时自动替换）
- ✅ POST 请求自动转为 JSON（数组数据）
- ✅ 返回解析后的数组

##### 微信支付V2通用接口

```php
<?php
$pay = \We::WePayOrder($config);

// POST请求 - 统一下单（自动签名，默认HMAC-SHA256）
$result = $pay->callApi(
    'https://api.mch.weixin.qq.com/pay/unifiedorder',
    [
        'body' => '测试商品',
        'out_trade_no' => time(),
        'total_fee' => '1',
        'openid' => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo',
        'trade_type' => 'JSAPI',
        'notify_url' => 'https://your-domain.com/notify.php',
        'spbill_create_ip' => '127.0.0.1',
    ],
    'POST'
);

// POST请求 - 使用MD5签名
$result = $pay->callApi(
    'https://api.mch.weixin.qq.com/pay/unifiedorder',
    $data,
    'POST',
    false,  // 不需要证书
    'MD5'   // 签名类型：MD5
);

// POST请求 - 需要证书（如退款）
$result = $pay->callApi(
    'https://api.mch.weixin.qq.com/secapi/pay/refund',
    $data,
    'POST',
    true,   // 需要证书
    'MD5'   // 签名类型
);

// GET请求 - 查询订单
$result = $pay->callApi(
    'https://api.mch.weixin.qq.com/pay/orderquery',
    ['out_trade_no' => '123456'],
    'GET'
);

// PUT/DELETE请求
$result = $pay->callApi($url, $data, 'PUT', false, 'MD5');
$result = $pay->callApi($url, $data, 'DELETE', false, 'MD5');
```

**参数说明：**

- `$url` (string) - 完整URL
- `$data` (array|string) - 请求参数（支持数组或字符串）
- `$method` (string) - 请求方法 GET|POST|PUT|DELETE|PATCH，默认 POST
- `$isCert` (bool) - 是否需要证书，默认 false
- `$signType` (string) - 签名类型 MD5|HMAC-SHA256，默认 HMAC-SHA256

**默认行为：**

- ✅ 自动签名（POST/PUT/PATCH/DELETE 请求）
- ✅ 返回解析后的数组（XML格式）

##### 微信支付V3通用接口

```php
<?php
$payment = \WePayV3\Order::instance($config);

// POST请求 - 创建JSAPI支付订单（相对路径）
$result = $payment->callApi(
    '/v3/pay/transactions/jsapi',
    [
        'appid' => $config['appid'],
        'mchid' => $config['mch_id'],
        'description' => '商品描述',
        'out_trade_no' => (string)time(),
        'notify_url' => 'https://your-domain.com/notify.php',
        'payer' => ['openid' => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo'],
        'amount' => ['total' => 2, 'currency' => 'CNY'],
    ],
    'POST'
);

// GET请求 - 查询订单（完整URL或相对路径）
$result = $payment->callApi(
    '/v3/pay/transactions/out-trade-no/123456?mchid=' . $config['mch_id'],
    '',
    'GET',
    true  // 验证响应签名
);

// PUT请求 - 更新资源
$result = $payment->callApi('/v3/some/resource/xxx', $data, 'PUT');

// DELETE请求 - 删除资源
$result = $payment->callApi('/v3/some/resource/xxx', '', 'DELETE');
```

**参数说明：**

- `$url` (string) - 完整URL或相对路径（如 `/v3/pay/transactions/jsapi`）
- `$data` (array|string) - 请求数据（数组会自动转为JSON）
- `$method` (string) - 请求方法 GET|POST|PUT|DELETE|PATCH|HEAD|OPTIONS，默认 POST
- `$verify` (bool) - 验证响应签名，默认 false

**默认行为：**

- ✅ 数组数据自动转为 JSON
- ✅ 返回解析后的数组

##### 支付宝通用接口

```php
<?php
$alipay = \We::AliPayWeb($config);

// GET请求 - 查询订单
$result = $alipay->callApi(
    'alipay.trade.query',  // API方法名（必填，第一参数）
    ['out_trade_no' => '123456'],
    'GET',
    false                  // 验证响应签名，默认false
);

// POST请求 - 订单退款
$result = $alipay->callApi(
    'alipay.trade.refund',  // API方法名（第一参数）
    [
        'out_trade_no' => '123456',
        'refund_amount' => '10',
    ],
    'POST',
    false                    // 不验证响应签名
);

// PUT/DELETE请求
$result = $alipay->callApi('alipay.some.method', $data, 'PUT', false);
```

**参数说明：**

- `$apiMethod` (string) - API方法名（如：`alipay.trade.query`），必填，第一参数
- `$data` (array|string) - 请求参数（支持数组或字符串）
- `$method` (string) - 请求方法 GET|POST|PUT|DELETE|PATCH，默认 GET
- `$verify` (bool) - 验证响应签名，默认 false

**默认行为：**

- ✅ 自动使用 gateway 作为请求URL
- ✅ 自动签名（默认开启）
- ✅ 返回解析后的数组

#### 自定义缓存

SDK 支持自定义缓存驱动，可以适配 Redis、Memcached 等缓存系统，特别适用于常驻内存框架（Workerman、Swoole 等）。

```php
<?php
// 配置自定义缓存（适配常驻内存框架）
\WeChat\Contracts\Tools::$cache_callable = [
    'set' => function ($name, $value, $expired = 360) {
        // 自定义缓存设置逻辑
        // $name: 缓存名称
        // $value: 缓存值
        // $expired: 过期时间（秒）
        return Redis::setex($name, $expired, serialize($value));
    },
    'get' => function ($name) {
        // 自定义缓存获取逻辑
        // $name: 缓存名称
        // 返回: 缓存值或 null
        $data = Redis::get($name);
        return $data ? unserialize($data) : null;
    },
    'del' => function ($name) {
        // 自定义缓存删除逻辑
        // $name: 缓存名称
        // 返回: boolean
        return Redis::del($name);
    },
    'put' => function ($name, $content) {
        // 自定义文件缓存逻辑（用于证书等文件）
        // $name: 文件名称
        // $content: 文件内容
        // 返回: 文件路径（必须是可读的文件路径）
        $file = '/path/to/cache/' . $name;
        file_put_contents($file, $content);
        return $file;
    },
];

// 注意：
// 1. 未配置自定义缓存时，默认使用文件缓存
// 2. 文件缓存路径可通过配置中的 'cache_path' 参数设置
// 3. SDK 会自动清理 CURL 临时缓存文件，无需手动处理
// 4. 在常驻内存框架中，建议使用 Redis 等外部缓存替代文件缓存
```

#### 错误处理

```php
<?php
try {
    $user = \We::WeChatUser($config);
    $result = $user->getUserList();
} catch (\WeChat\Exceptions\InvalidResponseException $e) {
    // 接口调用异常
    echo "接口错误：" . $e->getMessage();
} catch (\WeChat\Exceptions\LocalCacheException $e) {
    // 缓存异常
    echo "缓存错误：" . $e->getMessage();
} catch (Exception $e) {
    // 其他异常
    echo "系统错误：" . $e->getMessage();
}
```

### 🧩 小程序快速示例

#### 订阅消息发送

```php
<?php
$mini = \We::WeMiniNewtmpl($config);
$mini->send([
    'touser'      => '用户openid',
    'template_id' => '模板ID',
    'page'        => 'pages/index?foo=bar',
    'data'        => [
        'thing1' => ['value' => '订单已支付'],
        'time2'  => ['value' => '2024-01-01 12:00'],
    ],
]);
```

#### 内容安全校验

```php
<?php
$security = \We::WeMiniSecurity($config);

// 文本校验
$security->msgSecCheck('留言内容示例');

// 图片校验（文件流）
$imgContent = file_get_contents('/path/to/demo.jpg');
$security->imgSecCheck($imgContent);
```

## ⚙️ 配置说明

### 基础配置

#### 微信公众号配置

```php
<?php
$wechatConfig = [
    'appid'          => 'wx60a43dd8161666d4',
    'appsecret'      => 'b4e28746f1bd73b5c6684f5e01883c36',
    'token'          => 'your_wechat_token',
    'encodingaeskey' => 'your_encodingaeskey',
    'cache_path'     => '/path/to/cache',
];
```

#### 微信支付配置

```php
<?php
// 微信支付V2配置
$payConfig = [
    'appid'      => 'wx60a43dd8161666d4',
    'mch_id'     => '15293xxxxxx',
    'mch_key'    => 'your_merchant_key',
    'cache_path' => '/path/to/cache',
];

// 微信支付V3配置（推荐）
$payV3Config = [
    'appid'        => 'wx60a43dd8161666d4',
    'mch_id'       => '15293xxxxxx',
    'mch_v3_key'   => '98b7fxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    'cert_serial'  => '49055D67B2XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    'cert_public'  => '-----BEGIN CERTIFICATE-----...',
    'cert_private' => '-----BEGIN PRIVATE KEY-----...',
    'cache_path'   => '/path/to/cache',
    'cert_package' => [
        'PUB_KEY_ID_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx' => '-----BEGIN CERTIFICATE-----...'
    ],
];
```

#### 支付宝配置

```php
<?php
// 支付宝公钥模式配置
$alipayConfig = [
    'appid'       => '2021000122667306',
    'private_key' => 'MIIEowIBAAKCAQEAn...',
    'public_key'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...',
    'sign_type'   => 'RSA2',
    'notify_url'  => 'https://your-domain.com/alipay/notify',
    'return_url'  => 'https://your-domain.com/alipay/return',
    'charset'     => 'utf-8',
    'debug'       => false, // 生产环境设为false
];

// 支付宝证书模式配置
$alipayCertConfig = [
    'appid'            => '2021000122667306',
    'private_key'      => 'MIIEowIBAAKCAQEAn...',
    'app_cert_path'    => '/path/to/appPublicCert.crt',
    'alipay_root_path' => '/path/to/alipayRootCert.crt',
    'alipay_cert_path' => '/path/to/alipayPublicCert.crt',
    'sign_type'        => 'RSA2',
    'notify_url'       => 'https://your-domain.com/alipay/notify',
    'return_url'       => 'https://your-domain.com/alipay/return',
];
```

## ❓ 常见问题

### Q: 如何获取微信支付证书？

A: 登录微信商户平台，在"账户中心" -> "API 安全" -> "API 证书"中下载证书文件，或使用 API 证书下载工具。

### Q: 支付宝沙箱环境如何配置？

A: 设置 `debug => true` 并使用沙箱环境的 `appid` 和密钥即可。

### Q: AccessToken 过期怎么办？

A: SDK 已内置自动刷新机制，无需手动处理。如需自定义，可设置 `GetAccessTokenCallback` 回调函数。

### Q: 如何自定义缓存存储？

A: 配置 `\WeChat\Contracts\Tools::$cache_callable` 数组，实现自定义的缓存逻辑。

### Q: 支持哪些 PHP 版本？

A: 最低支持 PHP 5.4，建议使用 PHP 7.0+ 以获得最佳性能。

### Q: 如何处理支付回调？

A: 使用 `\WeChat\Receive` 类处理微信支付回调，使用 `\AliPay\Web` 的 `notify()` 方法处理支付宝回调。

### Q: 小程序数据解密失败？

A: 确保 `session_key` 有效且未过期，检查 `iv` 和 `encryptedData` 参数是否正确。

### Q: 如何调试接口调用？

A: 开启错误日志，查看具体的错误信息。SDK 会抛出详细的异常信息帮助定位问题。

### Q: 如何在常驻内存框架中使用？

A: SDK 会自动清理 CURL 缓存文件，无需额外配置。在常驻内存环境中，建议通过自定义缓存驱动（`\WeChat\Contracts\Tools::$cache_callable`）使用 Redis 等外部缓存，避免文件缓存带来的问题。

## 📁 文件说明

| 文件名               | 类名                  | 描述             | 类型    | 加载方法                      |
|-------------------|---------------------|----------------|-------|---------------------------|
| **支付宝支付**         |                     |                |       |                           |
| App.php           | AliPay\App          | 支付宝 App 支付     | 支付宝支付 | \We::AliPayApp()          |
| Bill.php          | AliPay\Bill         | 支付宝账单下载        | 支付宝支付 | \We::AliPayBill()         |
| Pos.php           | AliPay\Pos          | 支付宝刷卡支付        | 支付宝支付 | \We::AliPayPos()          |
| Scan.php          | AliPay\Scan         | 支付宝扫码支付        | 支付宝支付 | \We::AliPayScan()         |
| Transfer.php      | AliPay\Transfer     | 支付宝转账          | 支付宝支付 | \We::AliPayTransfer()     |
| Wap.php           | AliPay\Wap          | 支付宝 Wap 支付     | 支付宝支付 | \We::AliPayWap()          |
| Web.php           | AliPay\Web          | 支付宝 Web 支付     | 支付宝支付 | \We::AliPayWeb()          |
| **微信公众号**         |                     |                |       |                           |
| Card.php          | WeChat\Card         | 微信卡券接口支持       | 认证服务号 | \We::WeChatCard()         |
| Custom.php        | WeChat\Custom       | 微信客服消息接口支持     | 认证服务号 | \We::WeChatCustom()       |
| Draft.php         | WeChat\Draft        | 微信草稿箱          | 认证服务号 | \We::WeChatDraft()        |
| Freepublish.php   | WeChat\Freepublish  | 微信发布能力         | 认证服务号 | \We::WeChatFreepublish()  |
| Media.php         | WeChat\Media        | 微信媒体素材接口支持     | 认证服务号 | \We::WeChatMedia()        |
| Menu.php          | WeChat\Menu         | 微信菜单管理         | 认证服务号 | \We::WeChatMenu()         |
| Oauth.php         | WeChat\Oauth        | 微信网页授权消息类接口    | 认证服务号 | \We::WeChatOauth()        |
| Pay.php           | WeChat\Pay          | 微信支付类接口        | 认证服务号 | \We::WeChatPay()          |
| Product.php       | WeChat\Product      | 微信商店类接口        | 认证服务号 | \We::WeChatProduct()      |
| Qrcode.php        | WeChat\Qrcode       | 微信二维码接口支持      | 认证服务号 | \We::WeChatQrcode()       |
| Receive.php       | WeChat\Receive      | 微信推送事件消息处理支持   | 认证服务号 | \We::WeChatReceive()      |
| Scan.php          | WeChat\Scan         | 微信扫一扫接口支持      | 认证服务号 | \We::WeChatScan()         |
| Script.php        | WeChat\Script       | 微信前端 JSSDK 支持  | 认证服务号 | \We::WeChatScript()       |
| Shake.php         | WeChat\Shake        | 微信蓝牙设备揺一揺接口    | 认证服务号 | \We::WeChatShake()        |
| Tags.php          | WeChat\Tags         | 微信粉丝标签接口支持     | 认证服务号 | \We::WeChatTags()         |
| Template.php      | WeChat\Template     | 微信模板消息接口支持     | 认证服务号 | \We::WeChatTemplate()     |
| User.php          | WeChat\User         | 微信粉丝管理接口支持     | 认证服务号 | \We::WeChatUser()         |
| Wifi.php          | WeChat\Wifi         | 微信门店 WIFI 管理支持 | 认证服务号 | \We::WeChatWifi()         |
| **微信支付**          |                     |                |       |                           |
| Bill.php          | WePay\Bill          | 微信商户账单及评论      | 微信支付  | \We::WePayBill()          |
| Coupon.php        | WePay\Coupon        | 微信商户代金券        | 微信支付  | \We::WePayCoupon()        |
| Order.php         | WePay\Order         | 微信商户订单         | 微信支付  | \We::WePayOrder()         |
| Redpack.php       | WePay\Redpack       | 微信红包支持         | 微信支付  | \We::WePayRedpack()       |
| Refund.php        | WePay\Refund        | 微信商户退款         | 微信支付  | \We::WePayRefund()        |
| Transfers.php     | WePay\Transfers     | 微信商户打款到零钱      | 微信支付  | \We::WePayTransfers()     |
| TransfersBank.php | WePay\TransfersBank | 微信商户打款到银行卡     | 微信支付  | \We::WePayTransfersBank() |
| **微信小程序**         |                     |                |       |                           |
| Crypt.php         | WeMini\Crypt        | 微信小程序数据加密处理    | 微信小程序 | \We::WeMiniCrypt()        |
| Delivery.php      | WeMini\Delivery     | 小程序即时配送        | 微信小程序 | \We::WeMiniDelivery()     |
| Guide.php         | WeMini\Guide        | 小程序导购助手        | 微信小程序 | \We::WeMiniGuide()        |
| Image.php         | WeMini\Image        | 小程序图像处理        | 微信小程序 | \We::WeMiniImage()        |
| Live.php          | WeMini\Live         | 小程序直播接口        | 微信小程序 | \We::WeMiniLive()         |
| Logistics.php     | WeMini\Logistics    | 小程序物流助手        | 微信小程序 | \We::WeMiniLogistics()    |
| Message.php       | WeMini\Message      | 小程序动态消息        | 微信小程序 | \We::WeMiniMessage()      |
| Newtmpl.php       | WeMini\Newtmpl      | 小程序订阅消息        | 微信小程序 | \We::WeMiniNewtmpl()      |
| Ocr.php           | WeMini\Ocr          | 小程序 ORC 服务     | 微信小程序 | \We::WeMiniOcr()          |
| Operation.php     | WeMini\Operation    | 小程序运维中心        | 微信小程序 | \We::WeMiniOperation()    |
| Plugs.php         | WeMini\Plugs        | 微信小程序插件管理      | 微信小程序 | \We::WeMiniPlugs()        |
| Poi.php           | WeMini\Poi          | 小程序地址管理        | 微信小程序 | \We::WeMiniPoi()          |
| Qrcode.php        | WeMini\Qrcode       | 微信小程序二维码管理     | 微信小程序 | \We::WeMiniQrcode()       |
| Scheme.php        | WeMini\Scheme       | 小程序 URL-Scheme | 微信小程序 | \We::WeMiniScheme()       |
| Search.php        | WeMini\Search       | 小程序搜索          | 微信小程序 | \We::WeMiniSearch()       |
| Security.php      | WeMini\Security     | 小程序内容安全        | 微信小程序 | \We::WeMiniSecurity()     |
| Shipping.php      | WeMini\Shipping     | 小程序发货信息        | 微信小程序 | \We::WeMiniShipping()     |
| Soter.php         | WeMini\Soter        | 小程序生物认证        | 微信小程序 | \We::WeMiniSoter()        |
| Template.php      | WeMini\Template     | 微信小程序模板消息支持    | 微信小程序 | \We::WeMiniTemplate()     |
| Total.php         | WeMini\Total        | 微信小程序数据接口      | 微信小程序 | \We::WeMiniTotal()        |

## 📚 文档资源

### 官方文档

- **微信公众平台**：https://mp.weixin.qq.com/wiki
- **微信支付文档**：https://pay.weixin.qq.com/wiki/doc/api/index.html
- **支付宝开放平台**：https://opendocs.alipay.com/

### 项目资源

- **ThinkAdmin**：https://github.com/zoujingli/ThinkAdmin
- **在线文档**：https://www.kancloud.cn/zoujingli/wechat-developer
- **技术交流群**：QQ 群 513350915

### 代码仓库

- **GitHub**：https://github.com/zoujingli/WeChatDeveloper（主仓库）
- **Gitee**：https://gitee.com/zoujingli/WeChatDeveloper（国内镜像）
- **GitCode**：https://gitcode.com/ThinkAdmin/WeChatDeveloper（国内镜像）

## 🛡️ 安全说明

本项目经过全面的安全加固，包括：

- 输入验证和 XSS 防护
- 文件操作安全检查
- 加密算法安全升级
- 序列化数据验证
- 类型安全修复

建议在生产环境中：

- 定期更新到最新版本
- 配置 HTTPS 传输
- 设置适当的文件权限
- 监控异常访问日志

## 📄 版权说明

**WeChatDeveloper** 遵循 **MIT** 开源协议发布，并免费提供使用。

本项目包含的第三方源码和二进制文件的版权信息将另行标注，请在对应文件查看。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。

## 💝 赞助支持

如果这个项目对您有帮助，欢迎赞助支持！

![赞助](https://thinkadmin.top/static/img/pay.png)
