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
 * 发布能力
 * @author taoxin
 * @package WeChat
 */
class Freepublish extends BasicWeChat
{
    /**
     * 提交发布
     * @param string $mediaId 草稿 media_id
     * @return array 含 publish_id
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function submit($mediaId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/submit?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['media_id' => $mediaId]);
    }

    /**
     * 查询发布状态
     * @param string $publishId 发布任务 ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get($publishId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['publish_id' => $publishId]);
    }

    /**
     * 删除已发布文章
     * @param string $articleId 发布返回的 article_id
     * @param int $index 图文序号，1 开始，0 删除全部
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delete($articleId, $index = 0)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['article_id' => $articleId, 'index' => $index]);
    }

    /**
     * 获取已发布文章
     * @param string $articleId article_id
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getArticle($articleId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/getarticle?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['article_id' => $articleId]);
    }

    /**
     * 获取已发布列表
     * @param int $offset 起始偏移
     * @param int $count 数量 1-20
     * @param int $noContent 1 不返回 content
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function batchGet($offset = 0, $count = 20, $noContent = 0)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/batchget?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['no_content' => $noContent, 'offset' => $offset, 'count' => $count]);
    }
}