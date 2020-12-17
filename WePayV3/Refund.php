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

use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WePayV3\Contracts\BasicWePay;

/**
 * 订单退款接口
 * Class Refund
 * @package WePayV3
 */
class Refund extends BasicWePay
{
    /**
     * 创建退款订单
     * @param string $json 退款参数
     * @return array
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function create($json)
    {
        return $this->doRequest('POST', '/v3/ecommerce/refunds/apply', $json, true);
    }

    /**
     * 退款订单查询
     * @param string $refundNo 退款单号
     * @return array
     * @throws InvalidResponseException
     * @throws LocalCacheException
     */
    public function query($refundNo)
    {
        $pathinfo = "/v3/ecommerce/refunds/out-refund-no/{$refundNo}";
        return $this->doRequest('GET', "{$pathinfo}?sub_mchid={$this->config['mch_id']}", '', true);
    }

}