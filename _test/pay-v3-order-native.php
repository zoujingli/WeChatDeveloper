<?php
try {
    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备公众号配置参数
    $config = include "./pay-v3-config.php";

    // 3. 创建接口实例
    $payment = \WePayV3\Order::instance($config);

    // 4. 组装支付参数
    $order = (string)time();
    $result = $payment->create('native', [
        'appid'        => $config['appid'],
        'mchid'        => $config['mch_id'],
        'description'  => '商品描述',
        'out_trade_no' => $order,
        'notify_url'   => 'https://thinkadmin.top',
        'amount'       => ['total' => 2, 'currency' => 'CNY'],
    ]);

    echo '<pre>';
    echo "\n--- 创建支付参数 ---\n";
    var_export($result);

//  array('code_url' => 'weixin://wxpay/bizpayurl?pr=cdJXOVDzz');


    echo "\n\n--- 查询支付参数 ---\n";
    $result = $payment->query($order);
    var_export($result);

//    array(
//        'amount'           => array('payer_currency' => 'CNY', 'total' => 2),
//        'appid'            => 'wx60a43dd8161666d4',
//        'mchid'            => '1332187001',
//        'out_trade_no'     => '1669027871',
//        'promotion_detail' => array(),
//        'scene_info'       => array('device_id' => ''),
//        'trade_state'      => 'NOTPAY',
//        'trade_state_desc' => '订单未支付',
//    );

} catch (\Exception $exception) {
    // 出错啦，处理下吧
    echo $exception->getMessage() . PHP_EOL;
}