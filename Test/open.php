<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

try {

    // 1. 手动加载入口文件
    include "../include.php";

    $config = [
        'component_appid'          => 'test',
        'component_token'          => 'test',
        'component_appsecret'      => '71308e96a204296c57d7cd4b21b883e8',
        'component_encodingaeskey' => 'BJIUzE0gqlWy0GxfPp4J1oPTBmOrNDIGPNav1YFH5Z5',
        // 配置商户支付参数
        'mch_id'                   => "1332187001",
        'mch_key'                  => '11bd3d66d85f322a1e803cb587d18c3f',
        // 配置商户支付双向证书目录
        'ssl_key'                  => '',
        'ssl_cer'                  => '',

    ];
    // 开放平台获取授权公众号 AccessToken 处理
    $config['GetAccessTokenCallback'] = function ($authorizer_appid) use ($config) {
        $open = new \WeChat\Open($config);
        $authorizer_refresh_token = ''; // 从数据库去找吧，在授权绑定的时候获取到了
        $result = $open->refreshAccessToken($authorizer_appid, $authorizer_refresh_token);
        if (empty($result['authorizer_access_token'])) {
            throw new \WeChat\Exceptions\InvalidResponseException($result['errmsg'], '0');
        }
        $data = [
            'authorizer_access_token'  => $result['authorizer_access_token'],
            'authorizer_refresh_token' => $result['authorizer_refresh_token'],
        ];
        // 需要把$data记录到数据库
        return $result['authorizer_access_token'];
    };
    // 使用第三方服务创建接口实例
    $open = new \WeChat\Open($config);
    $wechat = $open->instance('授权公众号APPID', 'User');
} catch (Exception $e) {
    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;
}