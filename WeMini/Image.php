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
 * 小程序图像处理
 * @package WeMini
 */
class Image extends BasicWeChat
{

    /**
     * 图片智能裁剪
     * @param string $img_url 图片 URL（与 img 二选一）
     * @param string $img form-data 媒体文件（与 img_url 二选一）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function aiCrop($img_url, $img)
    {
        $url = "https://api.weixin.qq.com/cv/img/aicrop?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['img_url' => $img_url, 'img' => $img], true);
    }

    /**
     * 条码/二维码识别
     * @param string $img_url 图片 URL（与 img 二选一）
     * @param string $img form-data 媒体文件（与 img_url 二选一）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function scanQRCode($img_url, $img)
    {
        $url = "https://api.weixin.qq.com/cv/img/qrcode?img_url=ENCODE_URL&access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['img_url' => $img_url, 'img' => $img], true);
    }

    /**
     * 图片高清化
     * @param string $img_url 图片 URL（与 img 二选一）
     * @param string $img form-data 媒体文件（与 img_url 二选一）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function superresolution($img_url, $img)
    {
        $url = "https://api.weixin.qq.com/cv/img/qrcode?img_url=ENCODE_URL&access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['img_url' => $img_url, 'img' => $img], true);
    }
}