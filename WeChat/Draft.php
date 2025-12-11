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
 * 微信草稿箱管理
 * @author taoxin
 * @package WeChat
 */
class Draft extends BasicWeChat
{
    /**
     * 新建草稿
     * @param array $articles 图文数组 articles
     * @return array 草稿 media_id
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function add($articles)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/add?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['articles' => $articles]);
    }

    /**
     * 获取草稿
     * @param string $mediaId 草稿 media_id
     * @param string $outType 可选回调处理
     * @return array 草稿内容
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get($mediaId, $outType = null)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['media_id' => $mediaId]);
    }

    /**
     * 删除草稿
     * @param string $mediaId 草稿 media_id
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delete($mediaId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['media_id' => $mediaId]);
    }

    /**
     * 新增图文素材
     * @param array $data 图文 articles
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addNews($data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 修改草稿
     * @param string $media_id 草稿 media_id
     * @param int $index 文章序号（0 开始）
     * @param array $articles 文章内容
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function update($media_id, $index, $articles)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/update?access_token=ACCESS_TOKEN";
        $data = ['media_id' => $media_id, 'index' => $index, 'articles' => $articles];
        return $this->callPostApi($url, $data);
    }

    /**
     * 获取草稿总数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getCount()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/count?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 获取草稿列表
     * @param int $offset 起始位置
     * @param int $count 拉取数量 1-20
     * @param int $noContent 1 不返回 content，0 返回
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function batchGet($offset = 0, $count = 20, $noContent = 0)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/draft/batchget?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['no_content' => $noContent, 'offset' => $offset, 'count' => $count]);
    }
}
