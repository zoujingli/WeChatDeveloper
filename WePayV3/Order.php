<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace WePayV3;

use WeChat\Exceptions\InvalidArgumentException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WePayV3\Contracts\BasicWePay;

/**
 * 订单支付接口
 * Class Order
 * @package WePayV3
 */
class Order extends BasicWePay
{
    const WXPAY_H5 = 'h5';
    const WXPAY_APP = 'app';
    const WXPAY_JSAPI = 'jsapi';
    const WXPAY_NATIVE = 'native';

    /**
     * 创建支付订单
     * @param string $type 支付类型
     * @param string $json 支付参数
     * @return array
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function create($type, $json)
    {
        $types = [
            'h5'     => '/v3/pay/transactions/h5',
            'app'    => '/v3/pay/transactions/app',
            'jsapi'  => '/v3/pay/transactions/jsapi',
            'native' => '/v3/pay/transactions/native',
        ];
        if (empty($types[$type])) {
            throw new InvalidArgumentException("Payment {$type} not defined.");
        } else {
            return $this->doRequest('POST', $types[$type], $json, true);
        }
    }

    /**
     * 支付订单查询
     * @param string $orderNo 订单单号
     * @return array
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function query($orderNo)
    {
        $pathinfo = "/v3/pay/transactions/out-trade-no/{$orderNo}";
        return $this->doRequest('GET', "{$pathinfo}?mchid={$this->config['mch_id']}", '', true);
    }
}