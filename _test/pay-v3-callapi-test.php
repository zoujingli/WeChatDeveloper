<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/WeChatDeveloper
// | github 代码仓库：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

/**
 * 微信支付V3通用接口测试
 * 测试 callApi() 万能接口方法
 */

try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备支付配置参数
    $config = include "./pay-v3-config.php";

    // 3. 创建接口实例
    $payment = \WePayV3\Order::instance($config);

    echo "<h2>=== 微信支付V3通用接口测试 ===</h2>";

    // ============================================
    // 测试1: POST请求 - 使用相对路径创建订单
    // ============================================
    echo "<h3>测试1: POST请求 - 创建JSAPI支付订单（相对路径）</h3>";
    try {
        $result = $payment->callApi(
            '/v3/pay/transactions/jsapi',
            [
                'appid'        => $config['appid'],
                'mchid'        => $config['mch_id'],
                'description'  => '测试商品',
                'out_trade_no' => (string)time(),
                'notify_url'   => 'https://your-domain.com/notify.php',
                'payer'        => [
                    'openid' => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo', // 请替换为实际的openid
                ],
                'amount'       => [
                    'total'    => 1,
                    'currency' => 'CNY'
                ],
            ],
            'POST',
            false  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试2: GET请求 - 查询订单（完整URL）
    // ============================================
    echo "<h3>测试2: GET请求 - 查询订单（完整URL）</h3>";
    try {
        $outTradeNo = time() - 3600; // 使用一个可能存在的订单号
        $result = $payment->callApi(
            "https://api.mch.weixin.qq.com/v3/pay/transactions/out-trade-no/{$outTradeNo}?mchid={$config['mch_id']}",
            '', // GET请求数据为空
            'GET',
            false  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试3: POST请求 - 创建H5支付订单
    // ============================================
    echo "<h3>测试3: POST请求 - 创建H5支付订单</h3>";
    try {
        $result = $payment->callApi(
            '/v3/pay/transactions/h5',
            [
                'appid'        => $config['appid'],
                'mchid'        => $config['mch_id'],
                'description'  => '测试商品',
                'out_trade_no' => (string)(time() + 1),
                'notify_url'   => 'https://your-domain.com/notify.php',
                'amount'       => [
                    'total'    => 1,
                    'currency' => 'CNY'
                ],
                'scene_info'   => [
                    'h5_info'         => [
                        'type' => 'Wap'
                    ],
                    'payer_client_ip' => '127.0.0.1',
                ],
            ],
            'POST',
            false  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试4: POST请求 - 创建退款（需要证书）
    // ============================================
    echo "<h3>测试4: POST请求 - 创建退款</h3>";
    echo "<p style='color: orange;'>注意：此测试需要有效的订单号</p>";
    try {
        $result = $payment->callApi(
            '/v3/refund/domestic/refunds',
            [
                'out_trade_no'  => (string)(time() - 3600),
                'out_refund_no' => (string)time(),
                'amount'        => [
                    'refund'   => 1,
                    'total'    => 1,
                    'currency' => 'CNY'
                ],
            ],
            'POST',
            false  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
        echo "<p style='color: gray;'>这是正常的，因为退款需要有效的订单号</p>";
    }

    // ============================================
    // 测试5: GET请求 - 查询退款
    // ============================================
    echo "<h3>测试5: GET请求 - 查询退款</h3>";
    try {
        $outRefundNo = time() - 3600;
        $result = $payment->callApi(
            "/v3/refund/domestic/refunds/{$outRefundNo}",
            '',
            'GET',
            false  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试6: POST请求 - 使用数组数据（自动JSON编码）
    // ============================================
    echo "<h3>测试6: POST请求 - 使用数组数据（自动JSON编码）</h3>";
    try {
        $data = [
            'appid'        => $config['appid'],
            'mchid'        => $config['mch_id'],
            'description'  => '测试商品',
            'out_trade_no' => (string)(time() + 2),
            'notify_url'   => 'https://your-domain.com/notify.php',
            'amount'       => [
                'total'    => 1,
                'currency' => 'CNY'
            ],
        ];

        // callApi会自动将数组转为JSON
        $result = $payment->callApi(
            '/v3/pay/transactions/jsapi',
            $data,
            'POST'
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    echo "<h3 style='color: green;'>=== 所有测试完成 ===</h3>";

} catch (Exception $e) {

    // 出错啦，处理下吧
    echo "<h3 style='color: red;'>错误: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";

}

