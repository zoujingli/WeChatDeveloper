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

// 1. 手动加载入口文件
include "../include.php";

// 2. 准备公众号配置参数
$config = include "./alipay.php";

try {
    // 实例支付对象
    // $pay = \We::AliPayApp($config);
    // $pay = new \AliPay\App($config);
    $pay = \AliPay\App::instance($config);

    // 请参考（请求参数）：https://docs.open.alipay.com/api_1/alipay.trade.app.pay
    $result = $pay->apply([
        'out_trade_no' => time(), // 商户订单号
        'total_amount' => '1', // 支付金额
        'subject'      => '支付宝订单标题', // 支付订单描述
    ]);
    echo $result;
} catch (\Exception $e) {
    echo $e->getMessage();
}


