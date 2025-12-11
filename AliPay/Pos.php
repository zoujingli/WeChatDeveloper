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

namespace AliPay;

use WeChat\Contracts\BasicAliPay;

/**
 * 支付宝刷卡支付
 * @package AliPay
 */
class Pos extends BasicAliPay
{
    /**
     * Pos constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->options->set('method', 'alipay.trade.pay');
        $this->params->set('product_code', 'FACE_TO_FACE_PAYMENT');
    }

    /**
     * 刷卡支付（条码/声波）下单
     * @param array $options 订单参数（out_trade_no, auth_code, subject, total_amount 等）
     * @return array|bool 支付结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function apply($options)
    {
        return $this->getResult($options);
    }
}