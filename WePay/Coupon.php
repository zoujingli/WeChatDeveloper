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
 * 微信商户代金券
 * @package WePay
 */
class Coupon extends BasicWePay
{
    /**
     * 发放代金券（需证书）
     * @param array $options 代金券参数（coupon_stock_id, openid, partner_trade_no, op_user_id 等）
     * @return array 发券结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function create(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/send_coupon";
        return $this->callPostApi($url, $options, true, 'MD5');
    }

    /**
     * 查询代金券批次
     * @param array $options 查询参数（coupon_stock_id 等）
     * @return array 批次详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryStock(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/query_coupon_stock";
        return $this->callPostApi($url, $options, false);
    }

    /**
     * 查询单个代金券信息
     * @param array $options 查询参数（coupon_id, openid, stock_id 等）
     * @return array 代金券详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryInfo(array $options)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/query_coupon_stock";
        return $this->callPostApi($url, $options, false);
    }

}