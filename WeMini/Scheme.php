<?php

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 小程序 URL-Scheme
 * Class Scheme
 * @package WeMini
 */
class Scheme extends BasicWeChat
{

    /**
     * 创建 URL Scheme
     * @param array $data
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询 URL-Scheme
     * @param string $scheme
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query($scheme)
    {
        $url = 'https://api.weixin.qq.com/wxa/queryscheme?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callPostApi($url, ['scheme' => $scheme], true);
    }
}