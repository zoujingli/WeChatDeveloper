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
use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信商户退款
 * @package WePay
 */
class Refund extends BasicWePay
{

    /**
     * 申请退款接口（需要证书）
     * @param array $options 退款参数（transaction_id或out_trade_no, out_refund_no, total_fee, refund_fee等）
     * @return array 退款结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
        return $this->callPostApi($url, $options, true);
    }

    /**
     * 查询退款接口
     * @param array $options 查询参数（transaction_id, out_trade_no, out_refund_no, refund_id四选一）
     * @return array 退款详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function query(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';
        return $this->callPostApi($url, $options);
    }

    /**
     * 解析退款通知（自动解密req_info）
     * @param string|array $xml 退款通知XML数据，为空则从POST获取
     * @return array 解密后的退款通知数据
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function getNotify($xml = '')
    {
        $data = is_array($xml) ? $xml : Tools::xml2arr(empty($xml) ? Tools::getRawInput() : $xml);
        if (!isset($data['return_code']) || $data['return_code'] !== 'SUCCESS') {
            throw new InvalidResponseException('获取退款通知XML失败！');
        }
        try {
            $key = md5($this->config->get('mch_key'));
            $decrypt = base64_decode($data['req_info']);
            $response = openssl_decrypt($decrypt, 'aes-256-ecb', $key, OPENSSL_RAW_DATA);
            $data['result'] = Tools::xml2arr($response);
            return $data;
        } catch (\Exception $exception) {
            throw new InvalidDecryptException($exception->getMessage(), $exception->getCode());
        }
    }
}