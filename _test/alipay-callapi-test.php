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
 * 支付宝通用接口测试
 * 测试 callApi() 万能接口方法
 */

try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备支付宝配置参数
    $config = include "./alipay.php";

    // 3. 创建接口实例
    $alipay = \We::AliPayWeb($config);

    echo "<h2>=== 支付宝通用接口测试 ===</h2>";

    // ============================================
    // 测试1: GET请求 - 查询订单
    // ============================================
    echo "<h3>测试1: GET请求 - 查询订单</h3>";
    try {
        $result = $alipay->callApi(
            'alipay.trade.query',  // API方法名（必填，第一参数）
            [
                'out_trade_no' => time() - 3600, // 使用一个可能存在的订单号
            ],
            'GET',
            false                  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试2: GET请求 - 查询订单（验证签名）
    // ============================================
    echo "<h3>测试2: GET请求 - 查询订单（验证签名）</h3>";
    try {
        $result = $alipay->callApi(
            'alipay.trade.query',  // API方法名
            [
                'out_trade_no' => time() - 3600,
            ],
            'GET',
            true                   // 验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试3: POST请求 - 订单退款
    // ============================================
    echo "<h3>测试3: POST请求 - 订单退款</h3>";
    echo "<p style='color: orange;'>注意：此测试需要有效的订单号</p>";
    try {
        $result = $alipay->callApi(
            'alipay.trade.refund',  // API方法名（第一参数）
            [
                'out_trade_no'  => time() - 3600,
                'refund_amount' => '0.01',
            ],
            'POST',
            false                   // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
        echo "<p style='color: gray;'>这是正常的，因为退款需要有效的订单号</p>";
    }

    // ============================================
    // 测试4: GET请求 - 退款查询
    // ============================================
    echo "<h3>测试4: GET请求 - 退款查询</h3>";
    try {
        $result = $alipay->callApi(
            'alipay.trade.fastpay.refund.query',  // API方法名
            [
                'out_trade_no'   => time() - 3600,
                'out_request_no' => time() - 3600,
            ],
            'GET',
            false                                  // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试5: POST请求 - 关闭订单
    // ============================================
    echo "<h3>测试5: POST请求 - 关闭订单</h3>";
    try {
        $result = $alipay->callApi(
            'alipay.trade.close',  // API方法名
            [
                'out_trade_no' => time() - 3600,
            ],
            'POST',
            false                   // 不验证响应签名
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试6: PUT请求 - 更新资源（示例）
    // ============================================
    echo "<h3>测试6: PUT请求 - 更新资源（示例）</h3>";
    echo "<p style='color: gray;'>注意：此示例仅展示PUT请求用法，实际API可能不支持</p>";
    try {
        // PUT请求示例（如果API支持）
        // $result = $alipay->callApi(
        //     'alipay.some.method',  // API方法名
        //     ['key' => 'value'],
        //     'PUT',
        //     false
        // );
        echo '<pre>PUT请求用法示例（已注释）</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    echo "<h3 style='color: green;'>=== 所有测试完成 ===</h3>";
} catch (Exception $e) {

    // 出错啦，处理下吧
    echo "<h3 style='color: red;'>错误: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
