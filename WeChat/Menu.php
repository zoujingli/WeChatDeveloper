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

namespace WeChat;

use WeChat\Contracts\BasicWeChat;

/**
 * 微信菜单管理
 * @package WeChat
 */
class Menu extends BasicWeChat
{

    /**
     * 查询自定义菜单接口
     * @return array 菜单配置信息
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 删除自定义菜单接口
     * @return array 操作结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delete()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 创建自定义菜单接口
     * @param array $data 菜单配置（button数组，最多3个一级菜单，每个一级菜单最多5个二级菜单）
     * @return array 操作结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 创建个性化菜单接口
     * @param array $data 菜单配置（button数组和matchrule匹配规则）
     * @return array 返回menuid
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addConditional(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 删除个性化菜单接口
     * @param string $menuid 菜单ID
     * @return array 操作结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delConditional($menuid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['menuid' => $menuid]);
    }

    /**
     * 测试个性化菜单匹配结果接口
     * @param string $openid 用户openid
     * @return array 该用户匹配的菜单配置
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function tryConditional($openid)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/trymatch?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['user_id' => $openid]);
    }
}