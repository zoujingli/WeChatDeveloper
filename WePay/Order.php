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

namespace WePay;

use WeChat\Contracts\BasicWePay;
use WeChat\Contracts\Tools;

/**
 * 微信商户订单
 * @package WePay
 */
class Order extends BasicWePay
{

    /**
     * 统一下单接口
     * @param array $options 订单参数（out_trade_no, body, total_fee, notify_url, trade_type等）
     * @return array 返回prepay_id等支付参数
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        return $this->callPostApi($url, $options, false, 'MD5');
    }

    /**
     * 刷卡支付接口（被扫支付）
     * @param array $options 支付参数（auth_code, out_trade_no, body, total_fee等）
     * @return array 支付结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function micropay(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/micropay';
        return $this->callPostApi($url, $options, false, 'MD5');
    }

    /**
     * 查询订单接口
     * @param array $options 查询参数（transaction_id或out_trade_no二选一）
     * @return array 订单详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        return $this->callPostApi($url, $options);
    }

    /**
     * 关闭订单接口
     * @param string $outTradeNo 商户订单号
     * @return array 关闭结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function close($outTradeNo)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/closeorder';
        return $this->callPostApi($url, ['out_trade_no' => $outTradeNo]);
    }

    /**
     * 生成JSAPI支付参数（用于前端调起支付）
     * @param string $prepayId 统一下单返回的prepay_id
     * @return array JSAPI支付所需参数（appId, timeStamp, nonceStr, package, signType, paySign）
     */
    public function jsapiParams($prepayId)
    {
        $option = [];
        $option["appId"] = $this->config->get('appid');
        $option["timeStamp"] = (string)time();
        $option["nonceStr"] = Tools::createNoncestr();
        $option["package"] = "prepay_id={$prepayId}";
        $option["signType"] = "MD5";
        $option["paySign"] = $this->getPaySign($option, 'MD5');
        $option['timestamp'] = $option['timeStamp'];
        return $option;
    }

    /**
     * 生成Native支付二维码URL
     * @param string $productId 商品ID或订单号
     * @return string 支付二维码URL（weixin://wxpay/bizpayurl?xxx）
     */
    public function qrcParams($productId)
    {
        $data = [
            'appid'      => $this->config->get('appid'),
            'mch_id'     => $this->config->get('mch_id'),
            'time_stamp' => (string)time(),
            'nonce_str'  => Tools::createNoncestr(),
            'product_id' => (string)$productId,
        ];
        $data['sign'] = $this->getPaySign($data, 'MD5');
        return "weixin://wxpay/bizpayurl?" . http_build_query($data);
    }

    /**
     * 生成APP支付参数（用于移动应用调起支付）
     * @param string $prepayId 统一下单返回的prepay_id
     * @return array APP支付所需参数（appid, partnerid, prepayid, package, timestamp, noncestr, sign）
     */
    public function appParams($prepayId)
    {
        $data = [
            'appid'     => $this->config->get('appid'),
            'partnerid' => $this->config->get('mch_id'),
            'prepayid'  => (string)$prepayId,
            'package'   => 'Sign=WXPay',
            'timestamp' => (string)time(),
            'noncestr'  => Tools::createNoncestr(),
        ];
        $data['sign'] = $this->getPaySign($data, 'MD5');
        return $data;
    }

    /**
     * 撤销订单接口（刷卡支付专用，需要证书）
     * @param array $options 撤销参数（transaction_id或out_trade_no二选一）
     * @return array 撤销结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function reverse(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/reverse';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 授权码查询openid接口
     * @param string $authCode 授权码（用户微信中的条码或二维码信息）
     * @return array 包含openid等信息
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryAuthCode($authCode)
    {
        $url = 'https://api.mch.weixin.qq.com/tools/authcodetoopenid';
        return $this->callPostApi($url, ['auth_code' => $authCode], false, 'MD5', false);
    }

    /**
     * 交易保障接口（用于上报交易数据）
     * @param array $options 上报参数（interface_url, execute_time, return_code, result_code等）
     * @return array 上报结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function report(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/payitil/report';
        return $this->callPostApi($url, $options);
    }
}