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
 * 手机WAP网站支付支持
 * @package AliPay
 */
class Wap extends BasicAliPay
{
    /**
     * Wap constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->options->set('method', 'alipay.trade.wap.pay');
        $this->params->set('product_code', 'QUICK_WAP_WAY');
    }

    /**
     * 生成 WAP 支付表单 HTML
     * @param array $options 订单参数（out_trade_no, total_amount, subject, quit_url, return_url 等）
     * @return string 自动提交的支付表单 HTML
     */
    public function apply($options)
    {
        parent::applyData($options);
        return $this->buildPayHtml();
    }
}