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
 * 小程序ORC服务
 * @package WeMini
 */
class Ocr extends BasicWeChat
{
    /**
     * 银行卡 OCR
     * @param array $data 图片参数（img_url 或 img）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function bankcard($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/bankcard?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }

    /**
     * 营业执照 OCR
     * @param array $data 图片参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function businessLicense($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/bizlicense?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }

    /**
     * 驾驶证 OCR
     * @param array $data 图片参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function driverLicense($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/drivinglicense?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }

    /**
     * 身份证 OCR
     * @param array $data 图片参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function idcard($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/idcard?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }

    /**
     * 通用印刷体 OCR
     * @param array $data 图片参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function printedText($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/comm?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }

    /**
     * 行驶证 OCR
     * @param array $data 图片参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function vehicleLicense($data)
    {
        $url = 'https://api.weixin.qq.com/cv/ocr/driving?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, false);
    }
}