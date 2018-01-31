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

// 1. 手动加载入口文件
include 'include.php';

// 2. 准备公众号配置参数
$config = [
    'token'          => 'test',
    'appid'          => 'wx60a43dd8161666d4',
    'appsecret'      => '71308e96a204296c57d7cd4b21b883e8',
    'encodingaeskey' => 'BJIUzE0gqlWy0GxfPp4J1oPTBmOrNDIGPNav1YFH5Z5',
    // 配置商户支付参数
    'mch_id'         => "1235704602",
    'mch_key'        => 'IKI4kpHjU94ji3oqre5zYaQMwLHuZPmj',
    // 配置商户支付双向证书目录
    'ssl_key'        => '',
    'ssl_cer'        => '',
];

try {

    // 3. 实例对应的接口对象
    $user = new \WeChat\User($config);

    // 4. 调用接口对象方法
    $list = $user->getUserList();
    echo '<pre>';
    var_export($list);

} catch (Exception $e) {

    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;

}


