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
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信商户账单及评论
 * @package WePay
 */
class Bill extends BasicWePay
{
    /**
     * 下载对账单
     * @param array $options 账单参数（bill_date, bill_type 等）
     * @param null|string $outType 输出处理回调，为 null 则返回原始内容
     * @return bool|string 原始账单文本或回调结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function download(array $options, $outType = null)
    {
        $this->params->set('sign_type', 'MD5');
        $params = $this->params->merge($options);
        $params['sign'] = $this->getPaySign($params, 'MD5');
        $result = Tools::post('https://api.mch.weixin.qq.com/pay/downloadbill', Tools::arr2xml($params));
        if (is_array($jsonData = Tools::xml3arr($result))) {
            if ($jsonData['return_code'] !== 'SUCCESS') {
                throw new InvalidResponseException($jsonData['return_msg'], '0');
            }
        }
        return is_null($outType) ? $result : $outType($result);
    }


    /**
     * 拉取订单评价数据（需证书）
     * @param array $options 查询参数（bill_date, offset, limit 等）
     * @return array 评价数据
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function comment(array $options)
    {
        $url = 'https://api.mch.weixin.qq.com/billcommentsp/batchquerycomment';
        return $this->callPostApi($url, $options, true);
    }
}