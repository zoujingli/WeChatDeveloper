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

namespace WePayV3;

use WePayV3\Contracts\BasicWePay;

/**
 * 商家分账 (V3)
 * @package WePayV3
 */
class ProfitSharing extends BasicWePay
{
    /**
     * 请求分账
     * @param array $options 分账参数（transaction_id, out_order_no, receivers 等）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function create(array $options)
    {
        $options['appid'] = $this->config['appid'];
        return $this->doRequest('POST', '/v3/profitsharing/orders', json_encode($options, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 查询分账结果
     * @param string $outOrderNo 商户分账单号
     * @param string $transactionId 微信订单号
     * @return array 分账状态
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function query($outOrderNo, $transactionId)
    {
        $pathinfo = "/v3/profitsharing/orders/{$outOrderNo}?&transaction_id={$transactionId}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 解冻剩余资金
     * @param array $options 解冻参数（transaction_id, out_order_no, description）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function unfreeze(array $options)
    {
        return $this->doRequest('POST', '/v3/profitsharing/orders/unfreeze', json_encode($options, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 查询剩余待分金额
     * @param string $transactionId 微信订单号
     * @return array 待分金额
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function amounts($transactionId)
    {
        $pathinfo = "/v3/profitsharing/transactions/{$transactionId}/amounts";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 添加分账接收方
     * @param array $options 接收方信息（type, account, name 等，name 需 RSA）
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function addReceiver(array $options)
    {
        $options['appid'] = $this->config['appid'];
        if (isset($options['name'])) {
            $options['name'] = $this->rsaEncode($options['name']);
        }
        return $this->doRequest('POST', "/v3/profitsharing/receivers/add", json_encode($options, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 删除分账接收方
     * @param array $options 接收方信息（type, account）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function deleteReceiver(array $options)
    {
        $options['appid'] = $this->config['appid'];
        return $this->doRequest('POST', "/v3/profitsharing/receivers/delete", json_encode($options, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 请求分账回退
     * @param array $options 回退参数（out_return_no, out_order_no, return_account 等）
     * @return array 回退结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function backspace(array $options)
    {
        $options['appid'] = $this->config['appid'];
        return $this->doRequest('POST', "/v3/profitsharing/return-orders", json_encode($options, JSON_UNESCAPED_UNICODE), true);
    }
}
