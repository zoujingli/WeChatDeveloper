<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2023 ThinkAdmin [ thinkadmin.top ]
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

use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;
use WePayV3\Contracts\BasicWePay;

/**
 * 电商接口 | 订单退款接口
 * @deprecated
 * @class Refund
 * @package WePayV3
 */
class Refund extends BasicWePay
{
    /**
     * 创建退款订单
     * @param array $data 退款参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function create($data)
    {
        return $this->doRequest('POST', '/v3/ecommerce/refunds/apply', json_encode($data, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 退款订单查询
     * @param string $refundNo 退款单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function query($refundNo)
    {
        $pathinfo = "/v3/ecommerce/refunds/out-refund-no/{$refundNo}";
        return $this->doRequest('GET', "{$pathinfo}?sub_mchid={$this->config['mch_id']}", '', true);
    }

    /**
     * 获取退款通知
     * @param string $xml
     * @return array
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function notify($xml = '')
    {
        $data = Tools::xml2arr(empty($xml) ? Tools::getRawInput() : $xml);
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS') {
            throw new InvalidResponseException('获取退款通知XML失败！');
        }
        try {
            $key = md5($this->config['mch_v3_key']);
            $decrypt = base64_decode($data['req_info']);
            $response = openssl_decrypt($decrypt, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);
            $data['result'] = Tools::xml2arr($response);
            return $data;
        } catch (\Exception $exception) {
            throw new InvalidDecryptException($exception->getMessage(), $exception->getCode());
        }
    }
}