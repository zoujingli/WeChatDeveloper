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
 * 用户标签管理
 * @package WeChat
 */
class Tags extends BasicWeChat
{
    /**
     * 获取标签列表
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getTags()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/get?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 创建标签
     * @param string $name 标签名称
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function createTags($name)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['tag' => ['name' => $name]]);
    }

    /**
     * 更新标签
     * @param int $id 标签ID
     * @param string $name 标签名称
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function updateTags($id, $name)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['tag' => ['name' => $name, 'id' => $id]]);
    }

    /**
     * 删除标签
     * @param int $tagId 标签ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function deleteTags($tagId)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/tags/delete?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['tag' => ['id' => $tagId]]);
    }

    /**
     * 批量为用户打标签
     * @param array $openids openid 列表
     * @param int $tagId 标签ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchTagging(array $openids, $tagId)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['openid_list' => $openids, 'tagid' => $tagId]);
    }

    /**
     * 批量取消用户标签
     * @param array $openids openid 列表
     * @param int $tagId 标签ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchUntagging(array $openids, $tagId)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['openid_list' => $openids, 'tagid' => $tagId]);
    }

    /**
     * 获取用户标签列表
     * @param string $openid 用户 openid
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getUserTagId($openid)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/tags/getidlist?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['openid' => $openid]);
    }
}