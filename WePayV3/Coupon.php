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

use WeChat\Exceptions\InvalidResponseException;
use WePayV3\Contracts\BasicWePay;

/**
 * 微信支付代金券 (V3)
 * @package WePayV3
 */
class Coupon extends BasicWePay
{


    /**
     * 创建代金券批次
     * @param array $data 批次参数（stock_name, belong_merchant 等）
     * @return array|string
     * @throws InvalidResponseException
     */
    public function stocksCreate(array $data)
    {
        $path = "/v3/marketing/favor/coupon-stocks";
        return $this->doRequest('POST', $path, json_encode($data), true);
    }

    /**
     * 激活代金券批次
     * @param string $stock_id 批次号
     * @param string $stock_creator_mchid 创建批次的商户号
     * @return array|string
     * @throws InvalidResponseException
     */
    public function stocksStart($stock_id, $stock_creator_mchid)
    {
        $path = "/v3/marketing/favor/stocks/{$stock_id}/start";
        return $this->doRequest('POST', $path, json_encode(['stock_creator_mchid' => $stock_creator_mchid]), true);
    }

    /**
     * 暂停代金券批次
     * @param string $stock_id 批次号
     * @param string $stock_creator_mchid 创建批次的商户号
     * @return array|string
     * @throws InvalidResponseException
     */
    public function stocksPause($stock_id, $stock_creator_mchid)
    {
        $path = "/v3/marketing/favor/stocks/{$stock_id}/pause";
        return $this->doRequest('POST', $path, json_encode(['stock_creator_mchid' => $stock_creator_mchid]), true);
    }

    /**
     * 重启代金券批次
     * @param string $stock_id 批次号
     * @param string $stock_creator_mchid 创建批次的商户号
     * @return array|string
     * @throws InvalidResponseException
     */
    public function stocksRestart($stock_id, $stock_creator_mchid)
    {
        $path = "/v3/marketing/favor/stocks/{$stock_id}/restart";
        return $this->doRequest('POST', $path, json_encode(['stock_creator_mchid' => $stock_creator_mchid]), true);
    }

    /**
     * 查询批次详情
     * @param string $stock_id 批次号
     * @param string $stock_creator_mchid 创建批次的商户号
     * @return array|string 批次详情
     * @throws InvalidResponseException
     */
    public function stocksDetail($stock_id, $stock_creator_mchid)
    {
        $path = "/v3/marketing/favor/stocks/{$stock_id}?stock_creator_mchid={$stock_creator_mchid}";
        return $this->doRequest('GET', $path, '', true);
    }

    /**
     * 代金券批次可用商品
     * @param array $param 包含 stock_id 等
     * @return array|string
     * @throws InvalidResponseException
     */
    public function stocksItems(array $param)
    {
        $path = "/v3/marketing/favor/stocks/{$param['stock_id']}/items ";
        return $this->doRequest('POST', $path, json_encode($param), true);
    }

    /**
     * 设置消息通知地址
     * @param array $param 回调参数（notify_url, switch 等）
     * @return array|string
     * @throws InvalidResponseException
     */
    public function setCallbacks(array $param)
    {
        $path = "/v3/marketing/favor/callbacks";
        return $this->doRequest('POST', $path, json_encode($param), true);
    }

    /**
     * 发放代金券
     * @param array $param 请求参数（openid, stock_id 等）
     * @return array|string
     * @throws InvalidResponseException
     */
    public function couponsSend(array $param)
    {
        $path = "/v3/marketing/favor/users/{$param['openid']}/coupons";
        return $this->doRequest('POST', $path, json_encode($param), true);
    }

    /**
     * 查询用户名下券（按商户号）
     * @param array $param 请求参数（openid, appid, stock_id 等）
     * @return array|string 券列表
     * @throws InvalidResponseException
     */
    public function couponsList(array $param)
    {
        $path = "/v3/marketing/favor/users/{$param['openid']}/coupons";
        return $this->doRequest('POST', $path, json_encode($param), true);
    }

    /**
     * 查询单张代金券详情
     * @param string $openid 用户 openid
     * @param string $coupon_id 代金券 id
     * @param string $appid 公众账号或小程序 appid
     * @return array|string
     * @throws InvalidResponseException
     */
    public function couponsDetail($openid, $coupon_id, $appid)
    {
        $path = "/v3/marketing/favor/users/{$openid}/coupons/{$coupon_id}?appid={$appid}";
        return $this->doRequest('GET', $path, '', true);
    }
}
