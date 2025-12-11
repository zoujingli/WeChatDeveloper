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

use WeChat\Contracts\BasicWePay;
use WePay\Bill;
use WePay\Order;
use WePay\Refund;
use WePay\Transfers;
use WePay\TransfersBank;

/**
 * 微信支付商户聚合入口（V2）
 * @package WeChat\Contracts
 */
class Pay extends BasicWePay
{

    /**
     * 统一下单
     * @param array $options 下单参数（out_trade_no, body, total_fee, notify_url, trade_type 等）
     * @return array 预支付信息
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createOrder(array $options)
    {
        return Order::instance($this->config->get())->create($options);
    }

    /**
     * 刷卡支付（被扫）
     * @param array $options 支付参数（auth_code, out_trade_no, total_fee 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createMicropay($options)
    {
        return Order::instance($this->config->get())->micropay($options);
    }

    /**
     * 生成 JSAPI/H5 支付参数
     * @param string $prepay_id 统一下单返回的 prepay_id
     * @return array 前端调起参数
     */
    public function createParamsForJsApi($prepay_id)
    {
        return Order::instance($this->config->get())->jsapiParams($prepay_id);
    }

    /**
     * 生成 APP 支付参数
     * @param string $prepay_id 统一下单返回的 prepay_id
     * @return array APP 支付参数
     */
    public function createParamsForApp($prepay_id)
    {
        return Order::instance($this->config->get())->appParams($prepay_id);
    }

    /**
     * 生成 Native 支付二维码 URL
     * @param string $product_id 商品 ID 或订单号
     * @return string
     */
    public function createParamsForRuleQrc($product_id)
    {
        return Order::instance($this->config->get())->qrcParams($product_id);
    }

    /**
     * 查询订单
     * @param array $options 查询参数（transaction_id 或 out_trade_no）
     * @return array 订单详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryOrder(array $options)
    {
        return Order::instance($this->config->get())->query($options);
    }

    /**
     * 关闭订单
     * @param string $out_trade_no 商户订单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function closeOrder($out_trade_no)
    {
        return Order::instance($this->config->get())->close($out_trade_no);
    }

    /**
     * 申请退款
     * @param array $options 退款参数（out_trade_no/transaction_id，out_refund_no，total_fee，refund_fee 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createRefund(array $options)
    {
        return Refund::instance($this->config->get())->create($options);
    }

    /**
     * 查询退款
     * @param array $options 查询参数（transaction_id/out_trade_no/out_refund_no/refund_id 四选一）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryRefund(array $options)
    {
        return Refund::instance($this->config->get())->query($options);
    }

    /**
     * 交易保障上报
     * @param array $options 上报参数（interface_url, execute_time, return_code 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function report(array $options)
    {
        return Order::instance($this->config->get())->report($options);
    }

    /**
     * 授权码查询 openid
     * @param string $authCode 扫码支付授权码
     * @return array 含 openid
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryAuthCode($authCode)
    {
        return Order::instance($this->config->get())->queryAuthCode($authCode);
    }

    /**
     * 下载对账单
     * @param array $options 账单参数（bill_date, bill_type 等）
     * @param null|string $outType 输出处理回调，为 null 返回原始内容
     * @return bool|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function billDownload(array $options, $outType = null)
    {
        return Bill::instance($this->config->get())->download($options, $outType);
    }

    /**
     * 拉取订单评价数据（需证书）
     * @param array $options 查询参数（bill_date, offset, limit 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function billCommtent(array $options)
    {
        return Bill::instance($this->config->get())->comment($options);
    }

    /**
     * 企业付款到零钱
     * @param array $options 付款参数（partner_trade_no, openid, amount, desc 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createTransfers(array $options)
    {
        return Transfers::instance($this->config->get())->create($options);
    }

    /**
     * 查询企业付款到零钱
     * @param string $partner_trade_no 商户付款单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryTransfers($partner_trade_no)
    {
        return Transfers::instance($this->config->get())->query($partner_trade_no);
    }

    /**
     * 企业付款到银行卡
     * @param array $options 付款参数（partner_trade_no, enc_bank_no, enc_true_name, bank_code, amount 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createTransfersBank(array $options)
    {
        return TransfersBank::instance($this->config->get())->create($options);
    }

    /**
     * 查询企业付款到银行卡结果
     * @param string $partner_trade_no 商户付款单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryTransFresBank($partner_trade_no)
    {
        return TransfersBank::instance($this->config->get())->query($partner_trade_no);
    }
}