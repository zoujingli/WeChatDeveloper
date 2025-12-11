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
 * 微信公众号通用接口测试
 * 测试 callApi() 万能接口方法
 */

try {

    // 1. 手动加载入口文件
    include "../include.php";

    // 2. 准备公众号配置参数
    $config = include "./config.php";

    // 3. 创建接口实例
    $wechat = \We::WeChatUser($config);

    echo "<h2>=== 微信公众号通用接口测试 ===</h2>";

    // ============================================
    // 测试1: GET请求 - 使用完整URL（自动处理ACCESS_TOKEN）
    // ============================================
    echo "<h3>测试1: GET请求 - 获取用户列表（完整URL，自动处理ACCESS_TOKEN）</h3>";
    try {
        $result = $wechat->callApi(
            'https://api.weixin.qq.com/cgi-bin/user/get?ACCESS_TOKEN',
            [], // GET参数（URL中已包含）
            'GET'
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试2: GET请求 - 使用完整URL带参数
    // ============================================
    echo "<h3>测试2: GET请求 - 获取用户信息（完整URL带参数）</h3>";
    try {
        $result = $wechat->callApi(
            'https://api.weixin.qq.com/cgi-bin/user/info?ACCESS_TOKEN',
            [
                'openid' => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo', // 请替换为实际的openid
                'lang'   => 'zh_CN'
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
    // 测试3: POST请求 - 批量获取用户信息
    // ============================================
    echo "<h3>测试3: POST请求 - 批量获取用户信息（自动JSON编码）</h3>";
    try {
        $result = $wechat->callApi(
            'https://api.weixin.qq.com/cgi-bin/user/info/batchget?ACCESS_TOKEN',
            [
                'user_list' => [
                    ['openid' => 'o38gpszoJoC9oJYz3UHHf6bEp0Lo'], // 请替换为实际的openid
                    // ['openid' => 'o38gps3vNdCqaggFfrBRCRikwlWY'],
                ]
            ],
            'POST'
        );
        echo '<pre>';
        var_export($result);
        echo '</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试4: PUT请求 - 更新资源（如果API支持）
    // ============================================
    echo "<h3>测试4: PUT请求 - 更新资源（示例）</h3>";
    echo "<p style='color: gray;'>注意：此示例仅展示PUT请求用法，实际API可能不支持</p>";
    try {
        // PUT请求示例（如果API支持）
        // $result = $wechat->callApi(
        //     'https://api.weixin.qq.com/some/api?ACCESS_TOKEN',
        //     ['key' => 'value'],
        //     'PUT'
        // );
        echo '<pre>PUT请求用法示例（已注释）</pre>';
    } catch (Exception $e) {
        echo "错误: " . $e->getMessage() . PHP_EOL;
    }

    // ============================================
    // 测试5: 使用相对路径（需要手动拼接完整URL）
    // ============================================
    echo "<h3>测试5: GET请求 - 使用相对路径（需要完整URL）</h3>";
    try {
        // 注意：callApi需要完整URL，相对路径需要手动拼接
        $baseUrl = 'https://api.weixin.qq.com';
        $result = $wechat->callApi(
            $baseUrl . '/cgi-bin/user/get?ACCESS_TOKEN',
            [],
            'GET'
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

