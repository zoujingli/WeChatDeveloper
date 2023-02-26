<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2023 ThinkAdmin [ thinkadmin.top ]
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
你的微信商户证书公钥内容
-----END CERTIFICATE-----
CERT;

$certPrivate = <<<CERT
-----BEGIN PRIVATE KEY-----
你的微信商户证书私钥内容
-----END PRIVATE KEY-----
CERT;

return [
    // 商户绑定的公众号APPID
    'appid'        => '',
    // 微信商户编号ID
    'mch_id'       => '',
    // 微信商户V3接口密钥
    'mch_v3_key'   => '',
    // 微信商户证书公钥，支持证书内容或文件路径
    'cert_public'  => $certPublic,
    // 微信商户证书私钥，支持证书内容或文件路径
    'cert_private' => $certPrivate,
];