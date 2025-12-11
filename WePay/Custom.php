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
 * 微信扩展上报海关
 * @package WePay
 */
class Custom extends BasicWePay
{

    /**
     * 海关申报：订单附加信息提交
     * @param array $options 申报参数（transaction_id 或 out_trade_no，customs，mch_customs_no 等）
     * @return array 申报结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function add(array $options = [])
    {
        $url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclareorder';
        return $this->callPostApi($url, $options, false, 'MD5', false, false);
    }

    /**
     * 海关申报：订单附加信息查询
     * @param array $options 查询参数（transaction_id 或 out_trade_no，customs，mch_customs_no 等）
     * @return array 申报状态
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function get(array $options = [])
    {
        $url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/customs/customdeclarequery';
        return $this->callPostApi($url, $options, false, 'MD5', true, false);
    }


    /**
     * 海关申报：重推申报信息
     * @param array $options 重推参数（transaction_id 或 out_trade_no，customs，mch_customs_no 等）
     * @return array 重推结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function reset(array $options = [])
    {
        $url = 'https://api.mch.weixin.qq.com/cgi-bin/mch/newcustoms/customdeclareredeclare';
        return $this->callPostApi($url, $options, false, 'MD5', true, false);
    }

}