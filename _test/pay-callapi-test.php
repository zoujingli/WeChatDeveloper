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
 * 微信支付V2通用接口测试
 * 测试 callApi() 万能接口方法
 */

try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备支付配置参数
    $config = include "./pay-config.php";

    // 3. 创建接口实例
    $pay = \We::WePayOrder($config);

    echo "<h2>=== 微信支付V2通用接口测试 ===</h2>";

    // ============================================
    // 测试1: POST请求 - 统一下单（自动签名）
    // ============================================
    echo "<h3>测试1: POST请求 - 统一下单（自动签名）</h3>";
    try {
        $result = $pay->callApi(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            [
                'body'             => '测试商品',
                'out_trade_no'     => time(),
                'total_fee'        => '1',
                'openid'           => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo', // 请替换为实际的openid
                'trade_type'       => 'JSAPI',
                'notify_url'       => 'https://your-domain.com/notify.php',
                'spbill_create_ip' => '127.0.0.1',
            ],
            'POST',
            false,  // 不需要证书
            'MD5'   // 签名类型：MD5
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试2: GET请求 - 查询订单（不自动签名）
    // ============================================
    echo "<h3>测试2: GET请求 - 查询订单（不自动签名）</h3>";
    try {
        $result = $pay->callApi(
            'https://api.mch.weixin.qq.com/pay/orderquery',
            [
                'out_trade_no' => time() - 3600, // 使用一个可能存在的订单号
            ],
            'GET'
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试3: POST请求 - 使用HMAC-SHA256签名
    // ============================================
    echo "<h3>测试3: POST请求 - 使用HMAC-SHA256签名</h3>";
    try {
        $result = $pay->callApi(
            'https://api.mch.weixin.qq.com/pay/unifiedorder',
            [
                'body'             => '测试商品',
                'out_trade_no'     => time() + 1,
                'total_fee'        => '1',
                'openid'           => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo',
                'trade_type'       => 'JSAPI',
                'notify_url'       => 'https://your-domain.com/notify.php',
                'spbill_create_ip' => '127.0.0.1',
            ],
            'POST',
            false,           // 不需要证书
            'HMAC-SHA256'    // 使用HMAC-SHA256签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试4: POST请求 - 需要证书的接口（如退款）
    // ============================================
    echo "<h3>测试4: POST请求 - 需要证书的接口（如退款）</h3>";
    echo "<p style='color: orange;'>注意：此测试需要配置证书，如果未配置证书会报错</p>";
    try {
        $result = $pay->callApi(
            'https://api.mch.weixin.qq.com/secapi/pay/refund',
            [
                'out_trade_no'  => time() - 3600,
                'out_refund_no' => time(),
                'total_fee'     => '1',
                'refund_fee'    => '1',
            ],
            'POST',
            true,   // 需要证书
            'MD5'   // 签名类型：MD5
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
        echo "<p style='color: gray;'>这是正常的，因为退款接口需要证书且需要有效的订单号</p>";
    }

    // ============================================
    // 测试5: POST请求 - 不自动签名（手动处理）
    // ============================================
    echo "<h3>测试5: POST请求 - 不自动签名（手动处理签名）</h3>";
    try {
        // 手动构建参数和签名
        $data = [
            'body'             => '测试商品',
            'out_trade_no'     => time() + 2,
            'total_fee'        => '1',
            'openid'           => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo',
            'trade_type'       => 'JSAPI',
            'notify_url'       => 'https://your-domain.com/notify.php',
            'spbill_create_ip' => '127.0.0.1',
        ];

        // 手动添加签名
        $data['sign'] = $pay->getPaySign($data, 'MD5');

        // 注意：手动签名后，callApi仍会自动签名，所以此示例需要调整
        // 实际使用中，如果已经手动签名，应该直接使用 Tools::post 发送
        echo '<pre>注意：手动签名后，callApi仍会自动签名，建议直接使用 Tools::post 发送</pre>';
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
