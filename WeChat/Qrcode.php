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
 * 二维码管理
 * @package WeChat
 */
class Qrcode extends BasicWeChat
{

    /**
     * 创建二维码 ticket
     * @param string|int $scene 场景值（字符串或数字）
     * @param int $expire_seconds 过期时间，0 表示永久
     * @return array 含 ticket、url
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create($scene, $expire_seconds = 0)
    {
        if (is_integer($scene)) { // 二维码场景类型
            $data = ['action_info' => ['scene' => ['scene_id' => $scene]]];
        } else {
            $data = ['action_info' => ['scene' => ['scene_str' => $scene]]];
        }
        if ($expire_seconds > 0) { // 临时二维码
            $data['expire_seconds'] = $expire_seconds;
            $data['action_name'] = is_integer($scene) ? 'QR_SCENE' : 'QR_STR_SCENE';
        } else { // 永久二维码
            $data['action_name'] = is_integer($scene) ? 'QR_LIMIT_SCENE' : 'QR_LIMIT_STR_SCENE';
        }
        $url = "https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 通过 ticket 换取二维码 URL
     * @param string $ticket 二维码 ticket
     * @return string 图片 URL
     */
    public function url($ticket)
    {
        return "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=" . urlencode($ticket);
    }

    /**
     * 长链接转短链接
     * @param string $longUrl 长链接
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function shortUrl($longUrl)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['action' => 'long2short', 'long_url' => $longUrl]);
    }
}