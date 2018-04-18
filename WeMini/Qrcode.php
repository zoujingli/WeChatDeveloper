<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 微信小程序二维码管理
 * Class Qrcode
 * @package WeMini
 */
class Qrcode extends BasicWeChat
{

    /**
     * 获取小程序码（永久有效）
     * 接口A: 适用于需要的码数量较少的业务场景
     * @param string $path 不能为空，最大长度 128 字节
     * @param integer $width 二维码的宽度
     * @param bool $auto_color 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
     * @param array $line_color auto_color 为 false 时生效
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getCode($path, $width = 430, $auto_color = false, $line_color = ["r" => "0", "g" => "0", "b" => "0"])
    {
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        $data = ['path' => $path, 'width' => $width, 'auto_color' => false, 'line_color' => $line_color];
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取小程序码（永久有效）
     * 接口B：适用于需要的码数量极多的业务场景
     * @param string $scene 最大32个可见字符，只支持数字
     * @param string $page 必须是已经发布的小程序存在的页面
     * @param integer $width 二维码的宽度
     * @param bool $auto_color 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
     * @param array $line_color auto_color 为 false 时生效
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getCodeUnlimit($scene, $page, $width = 430, $auto_color = false, $line_color = ["r" => "0", "g" => "0", "b" => "0"])
    {
        $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        $data = ['scene' => $scene, 'width' => $width, 'auto_color' => false, 'line_color' => $line_color];
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取小程序二维码（永久有效）
     * 接口C：适用于需要的码数量较少的业务场景
     * @param string $path 不能为空，最大长度 128 字节
     * @param integer $width 二维码的宽度
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createQrcode($path, $width = 430)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['path' => $path, 'width' => $width], true);
    }

}