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
 * 支付宝转账到账户
 * @package AliPay
 */
class Transfer extends BasicAliPay
{

    /**
     * 旧版：向支付宝账户转账（toaccount.transfer）
     * @param array $options 转账参数（out_biz_no, payee_type, payee_account, amount, remark 等）
     * @return array 转账结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function apply($options)
    {
        $this->options->set('method', 'alipay.fund.trans.toaccount.transfer');
        return $this->getResult($options);
    }

    /**
     * 新版：统一转账接口（uni.transfer）
     * @param array $options 转账参数（out_biz_no, trans_amount, product_code, payee_info 等）
     * @return array 转账结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create($options = [])
    {
        $this->options->set('method', 'alipay.fund.trans.uni.transfer');
        return $this->getResult($options);
    }

    /**
     * 新版：转账业务单据查询
     * @param array $options 查询参数（out_biz_no 或 order_id）
     * @return array 查询结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryResult($options = [])
    {
        $this->options->set('method', 'alipay.fund.trans.common.query');
        return $this->getResult($options);
    }

    /**
     * 新版：资金账户余额查询
     * @param array $options 查询参数（alipay_user_id 或 user_id，可选 account_type）
     * @return array 账户余额等信息
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryAccount($options = [])
    {
        $this->options->set('method', 'alipay.fund.account.query');
        return $this->getResult($options);
    }
}