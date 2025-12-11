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

use WeChat\Contracts\BasicPushEvent;

/**
 * 公众号推送管理
 * @package WeChat
 */
class Receive extends BasicPushEvent
{

    /**
     * 转发到多客服
     * @param string $account 可选客服账号
     * @return $this
     */
    public function transferCustomerService($account = '')
    {
        $this->message = [
            'CreateTime'   => time(),
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'MsgType'      => 'transfer_customer_service',
        ];
        empty($account) || $this->message['TransInfo'] = ['KfAccount' => $account];
        return $this;
    }

    /**
     * 设置文本消息
     * @param string $content 文本内容
     * @return $this
     */
    public function text($content = '')
    {
        $this->message = [
            'MsgType'      => 'text',
            'CreateTime'   => time(),
            'Content'      => $content,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
        ];
        return $this;
    }

    /**
     * 设置图文消息
     * @param array $newsData Articles 列表
     * @return $this
     */
    public function news($newsData = [])
    {
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'news',
            'Articles'     => $newsData,
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'ArticleCount' => count($newsData),
        ];
        return $this;
    }

    /**
     * 设置图片消息
     * @param string $mediaId 图片 media_id
     * @return $this
     */
    public function image($mediaId = '')
    {
        $this->message = [
            'MsgType'      => 'image',
            'CreateTime'   => time(),
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Image'        => ['MediaId' => $mediaId],
        ];
        return $this;
    }

    /**
     * 设置语音消息
     * @param string $mediaid 语音 media_id
     * @return $this
     */
    public function voice($mediaid = '')
    {
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'voice',
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Voice'        => ['MediaId' => $mediaid],
        ];
        return $this;
    }

    /**
     * 设置视频消息
     * @param string $mediaid 视频 media_id
     * @param string $title 标题
     * @param string $description 描述
     * @return $this
     */
    public function video($mediaid = '', $title = '', $description = '')
    {
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'video',
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Video'        => [
                'Title'       => $title,
                'MediaId'     => $mediaid,
                'Description' => $description,
            ],
        ];
        return $this;
    }

    /**
     * 设置音乐消息
     * @param string $title 标题
     * @param string $desc 描述
     * @param string $musicurl 音乐链接
     * @param string $hgmusicurl 高清链接
     * @param string $thumbmediaid 缩略图 media_id
     * @return $this
     */
    public function music($title, $desc, $musicurl, $hgmusicurl = '', $thumbmediaid = '')
    {
        $this->message = [
            'CreateTime'   => time(),
            'MsgType'      => 'music',
            'ToUserName'   => $this->getOpenid(),
            'FromUserName' => $this->getToOpenid(),
            'Music'        => [
                'Title'       => $title,
                'Description' => $desc,
                'MusicUrl'    => $musicurl,
                'HQMusicUrl'  => $hgmusicurl,
            ],
        ];
        if ($thumbmediaid) {
            $this->message['Music']['ThumbMediaId'] = $thumbmediaid;
        }
        return $this;
    }
}