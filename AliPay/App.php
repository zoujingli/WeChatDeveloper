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
 * 支付宝App支付网关
 * @package AliPay
 */
class App extends BasicAliPay
{

    /**
     * 构造函数
     * @param array $options 支付宝配置参数
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->options->set('method', 'alipay.trade.app.pay');
        $this->params->set('product_code', 'QUICK_MSECURITY_PAY');
    }

    /**
     * 生成APP支付参数字符串
     * @param array $options 订单参数（out_trade_no, total_amount, subject等）
     * @return string URL编码后的参数字符串（用于APP调起支付）
     */
    public function apply($options)
    {
        $this->applyData($options);
        return http_build_query($this->options->get());
    }
}