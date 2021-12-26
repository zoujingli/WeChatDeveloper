<?php

namespace WeChat;

use WeChat\Contracts\BasicWeChat;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 发布能力
 * Class Freepublish
 * @author taoxin
 * @package WeChat
 */
class Freepublish extends BasicWeChat
{
    /**
     * 发布接口
     * 开发者需要先将图文素材以草稿的形式保存（见“草稿箱/新建草稿”，如需从已保存的草稿中选择，见“草稿箱/获取草稿列表”）
     * @param $media_id 选择要发布的草稿的media_id
     * @return array
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function submit($media_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/submit?access_token=ACCESS_TOKEN";
        return $this->httpPostForJson($url, ['media_id' => $media_id]);
    }

    /**
     * 发布状态轮询接口
     * @param string $media_id
     * @param string $outType 返回处理函数
     * @return array|string
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function get($publish_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/get?access_token=ACCESS_TOKEN";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['publish_id' => $publish_id]);
    }

    /**
     * 删除发布
     * 发布成功之后，随时可以通过该接口删除。此操作不可逆，请谨慎操作。
     * @param $article_id 成功发布时返回的 article_id
     * @param int $index 要删除的文章在图文消息中的位置，第一篇编号为1，该字段不填或填0会删除全部文章
     * @return array
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function delete($article_id, $index = 0)
    {

        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/delete?access_token=ACCESS_TOKEN";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['article_id' => $article_id, 'index' => $index]);
    }


    /**
     * 通过 article_id 获取已发布文章
     * @param $article_id 要获取的草稿的article_id
     * @return array
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function getArticle($article_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/getarticle?access_token=ACCESS_TOKEN";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['article_id' => $article_id]);
    }


    /**
     * 获取成功发布列表
     * @param int $offset 从全部素材的该偏移位置开始返回，0表示从第一个素材返回
     * @param int $count 返回素材的数量，取值在1到20之间
     * @param int $no_content 1 表示不返回 content 字段，0 表示正常返回，默认为 0
     * @return array
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function batchGet($offset = 0, $count = 20, $no_content = 0)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/freepublish/batchget?access_token=ACCESS_TOKEN";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['no_content' => $no_content, 'offset' => $offset, 'count' => $count]);
    }

}
