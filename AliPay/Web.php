<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
 * 支付宝网站支付
 * Class Web
 * @package AliPay
 */
class Web extends BasicAliPay
{
    /**
     * Web constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        parent::__construct($options);
        $this->options->set('method', 'alipay.trade.page.pay');
        $this->params->set('product_code', 'FAST_INSTANT_TRADE_PAY');
    }

    /**
     * 创建数据操作
     * @param array $options
     * @return string
     */
    public function apply($options)
    {
        parent::applyData($options);
        return $this->buildPayHtml();
    }
}