<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2022 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
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

    // 2. 准备公众号配置参数
    $config = include "./config.php";

    // 3. 创建接口实例
    // $menu = \We::WeChatMenu($config);
    // $menu = new \WeChat\Menu($config);
    $menu = \WeChat\Menu::instance($config);

    // 4. 获取菜单数据
    $result = $menu->get();

    var_export($result);

} catch (Exception $e) {

    // 出错啦，处理下吧
    echo $e->getMessage() . PHP_EOL;

}