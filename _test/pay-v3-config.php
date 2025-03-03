<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/WeChatDeveloper
// | github 代码仓库：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

$certPublic = <<<CERT
-----BEGIN CERTIFICATE-----
具体证书全文内容
-----END CERTIFICATE-----
CERT;

$certPrivate = <<<CERT
-----BEGIN PRIVATE KEY-----
具体证书全文内容
-----END PRIVATE KEY-----
CERT;

// 支付证书内容
$certPayment = <<<CERT
-----BEGIN PUBLIC KEY-----
具体证书全文内容
-----END PUBLIC KEY-----
CERT;


// =====================================================
// 配置缓存处理函数 ( 适配其他环境 )
// -----------------------------------------------------
// 数据缓存 (set|get|del) 操作可以将缓存写到任意位置或Redis
// 文件缓存 (put) 只能写在本地服务器，还需要返回可读的文件路径
// 未配置自定义缓存处理机制时，默认在 cache_path 写入文件缓存
// // =====================================================
// \WeChat\Contracts\Tools::$cache_callable = [
//    'set' => function ($name, $value, $expired = 360) {
//        var_dump(func_get_args());
//         return $value;
//    },
//    'get' => function ($name) {
//        var_dump(func_get_args());
//        return $value;
//    },
//    'del' => function ($name) {
//        var_dump(func_get_args());
//        return true;
//    },
//    'put' => function ($name) {
//        var_dump(func_get_args());
//        return $filePath;
//    },
// ];

return [
    // 可选，公众号APPID
    'appid'           => 'wxbbc5782d4d77a43b',
    // 必填，微信商户编号ID
    'mch_id'          => '1643053551',
    // 必填，微信商户V3接口密钥
    'mch_v3_key'      => 'ThinkAdminThinkAdminThinkAdminTh',
    // 可选，微信商户证书序列号，可从公钥中提取，请求签名使用
    'cert_serial'     => '',
    // 必填，微信商户证书公钥，支持证书内容或文件路径，仅用于提取序号
    'cert_public'     => $certPublic,
    // 必填，微信商户证书私钥，支持证书内容或文件路径，用请求数据签名
    'cert_private'    => $certPrivate,
    // 可选，微信平台证书序号或支付证书序号，用于接口请求序号
    'mp_cert_serial'  => 'PUB_KEY_ID_0116430535512025030300389200001906',
    // 可选，微信平台证书内容或支付证书内容
    'mp_cert_content' => $certPayment,
    // 可选，运行时的文件缓存路径
    'cache_path'      => ''
];