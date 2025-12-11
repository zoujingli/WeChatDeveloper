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
 * 小程序运费险
 * @package WeMini
 */
class Insurance extends BasicWeChat
{

    /**
     * 开通无忧退货
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function open()
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/open?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, [], true);
    }

    /**
     * 查询开通状态
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryOpen()
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/query_open?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, [], true);
    }

    /**
     * 发货投保
     * @param array $data 投保数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createOrder($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/createorder?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 退货理赔
     * @param array $data 理赔数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function claim($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/claim?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 申请充值订单号
     * @param array $data 充值请求
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createChargeId($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/createchargeid?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 申请支付
     * @param array $data 支付数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function applyPay($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/applypay?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 拉取充值订单
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPayOrderList($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/getpayorderlist?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }


    /**
     * 保险退款
     * @param array $data 退款参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function refund($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/refund?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取保费摘要
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getSummary($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/getsummary?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 拉取保单信息
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getOrderList($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/getorderlist?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 设置告警余额
     * @param array $data 配置参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateNotifyFunds($data)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/insurance_freight/update_notify_funds?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 创建退货 ID
     * @param array $data 退货参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function returnAdd($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/no_worry_return/add?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询退货 ID 状态
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function returnGet($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/no_worry_return/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 解绑退货 ID
     * @param array $data 解绑参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function returnUbind($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/no_worry_return/unbind?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }
}