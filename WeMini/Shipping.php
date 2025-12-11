<?php

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 小程序发货信息管理
 * @package WeMini
 */
class Shipping extends BasicWeChat
{

    /**
     * 录入发货信息
     * @param array $data 发货数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function upload($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/upload_shipping_info?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 合单发货录入
     * @param array $data 发货数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function combined($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/upload_combined_shipping_info?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询发货状态
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/get_order?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询发货订单列表
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function qlist($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/get_order_list?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 提醒确认收货
     * @param array $data 提醒参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function confirm($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/notify_confirm_receive?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 设置消息跳转路径
     * @param array $data 路径参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setJump($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/set_msg_jump_path?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询是否开通发货管理
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function isTrade($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/is_trade_managed?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询交易结算确认状态
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function isCompleted($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/sec/order/is_trade_management_confirmation_completed?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }
}