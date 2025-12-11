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

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 小程序模板消息
 * @package WeMini
 */
class Template extends BasicWeChat
{

    /**
     * 获取模板库标题列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getTemplateLibraryList()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/list?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['offset' => '0', 'count' => '20'], true);
    }

    /**
     * 获取模板关键词库
     * @param string $template_id 模板标题ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getTemplateLibrary($template_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/library/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['id' => $template_id], true);
    }

    /**
     * 组合关键词并添加模板
     * @param string $template_id 模板标题ID
     * @param array $keyword_id_list 关键词 ID 列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addTemplate($template_id, array $keyword_id_list)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/add?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['id' => $template_id, 'keyword_id_list' => $keyword_id_list], true);
    }

    /**
     * 获取帐号模板列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getTemplateList()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/list?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['offset' => '0', 'count' => '20'], true);
    }

    /**
     * 删除模板
     * @param string $template_id 模板 ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delTemplate($template_id)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/template/del?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['template_id' => $template_id], true);
    }

    /**
     * 发送模板消息
     * @param array $data 消息体（touser, template_id, form_id, data 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function send(array $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }
}