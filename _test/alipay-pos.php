<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/WeChatDeveloper
// | github 代码仓库：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

// 1. 手动加载入口文件
include "../include.php";

// 2. 准备公众号配置参数
$config = include "./alipay.php";

try {
    // 实例支付对象
    // $pay = We::AliPayPos($config);
    // $pay = new \AliPay\Pos($config);
    $pay = \AliPay\Pos::instance($config);

    // 参数链接：https://docs.open.alipay.com/api_1/alipay.trade.pay
    $result = $pay->apply([
        'out_trade_no' => '4312412343', // 订单号
        'total_amount' => '13', // 订单金额，单位：元
        'subject'      => '订单商品标题', // 订单商品标题
        'auth_code'    => '123456', // 授权码
    ]);

    echo '<pre>';
    var_export($result);
} catch (Exception $e) {
    echo $e->getMessage();
}


