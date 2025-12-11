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

/**
 * 微信分账
 * @package WePay
 */
class ProfitSharing extends BasicWePay
{

    /**
     * 请求单次分账（需证书）
     * @param array $options 分账参数（transaction_id, out_order_no, receivers 等）
     * @return array 分账结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharing(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 请求多次分账（需证书）
     * @param array $options 分账参数（transaction_id, out_order_no, receivers 等）
     * @return array 分账结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function multiProfitSharing(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 查询分账结果
     * @param array $options 查询参数（transaction_id 与 out_order_no）
     * @return array 分账状态
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingQuery(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingquery';
        return $this->callPostApi($url, $options);
    }

    /**
     * 添加分账接收方
     * @param array $options 接收方信息（type, account, name, relation_type 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingAddReceiver(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';
        return $this->callPostApi($url, $options);
    }

    /**
     * 删除分账接收方
     * @param array $options 接收方信息（type, account）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingRemoveReceiver(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingremovereceiver';
        return $this->callPostApi($url, $options);
    }

    /**
     * 完结分账（需证书）
     * @param array $options 完结参数（transaction_id, out_order_no, description 等）
     * @return array 完结结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingFinish(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 查询订单待分账金额
     * @param array $options 查询参数（transaction_id）
     * @return array 待分账金额
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingOrderAmountQuery(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingorderamountquery';
        return $this->callPostApi($url, $options);
    }

    /**
     * 分账回退（需证书）
     * @param array $options 回退参数（out_return_no, out_order_no, return_account_type 等）
     * @return array 回退结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingReturn(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingreturn';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 回退结果查询
     * @param array $options 查询参数（out_return_no 与 out_order_no）
     * @return array 回退状态
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function profitSharingReturnQuery(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingreturnquery';
        return $this->callPostApi($url, $options);
    }
}
