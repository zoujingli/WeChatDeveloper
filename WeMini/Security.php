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
 * 小程序内容安全
 * @package WeMini
 */
class Security extends BasicWeChat
{

    /**
     * 图片内容安全校验
     * @param string $media 图片文件
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function imgSecCheck($media)
    {
        $url = 'https://api.weixin.qq.com/wxa/img_sec_check?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['media' => $media], false, ['headers' => ['Content-Type: application/octet-stream']]);
    }

    /**
     * 异步校验媒体（图片/音频）
     * @param string $media_url 媒体 URL
     * @param string $media_type 1 音频 | 2 图片
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function mediaCheckAsync($media_url, $media_type)
    {
        $url = 'https://api.weixin.qq.com/wxa/media_check_async?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['media_url' => $media_url, 'media_type' => $media_type], true);
    }

    /**
     * 文本内容安全校验
     * @param string $content 文本内容
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function msgSecCheck($content)
    {
        $url = 'https://api.weixin.qq.com/wxa/msg_sec_check?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['content' => $content], true);
    }
}