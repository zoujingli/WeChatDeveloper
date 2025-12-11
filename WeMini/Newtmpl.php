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
 * 小程序订阅消息
 * @package WeMini
 */
class Newtmpl extends BasicWeChat
{
    /**
     * 添加账号类目
     * @param array $data 类目信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addCategory($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/addcategory?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取账号类目
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getCategory()
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/getcategory?access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }

    /**
     * 删除账号类目
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function deleteCategory()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/deletecategory?access_token=TOKEN';
        return $this->callPostApi($url, [], true);
    }

    /**
     * 获取类目下公共模板标题
     * @param string $ids 类目ID，逗号分隔
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPubTemplateTitleList($ids)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/getpubtemplatetitles?access_token=ACCESS_TOKEN';
        $url .= '&' . http_build_query(['ids' => $ids, 'start' => '0', 'limit' => '30']);
        return $this->callGetApi($url);
    }

    /**
     * 获取模板标题关键词
     * @param string $tid 模板标题ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPubTemplateKeyWordsById($tid)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/getpubtemplatekeywords?access_token=ACCESS_TOKEN';
        $url .= '&' . http_build_query(['tid' => $tid]);
        return $this->callGetApi($url);
    }

    /**
     * 组合模板并入库
     * @param string $tid 模板标题ID
     * @param array $kidList 关键词ID列表
     * @param string $sceneDesc 场景描述
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addTemplate($tid, array $kidList, $sceneDesc = '')
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['tid' => $tid, 'kidList' => $kidList, 'sceneDesc' => $sceneDesc], false);
    }

    /**
     * 获取个人模板列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getTemplateList()
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }

    /**
     * 删除个人模板
     * @param string $priTmplId 模板ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delTemplate($priTmplId)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/deltemplate?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['priTmplId' => $priTmplId], true);
    }

    /**
     * 发送订阅消息
     * @param array $data 消息数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function send(array $data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }
}