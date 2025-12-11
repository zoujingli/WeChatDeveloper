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

namespace AliPay;

use WeChat\Contracts\BasicAliPay;

/**
 * 支付宝标准接口
 * @package AliPay
 */
class Trade extends BasicAliPay
{

    /**
     * 设置交易接口方法名
     * @param string $method 如 alipay.trade.close 等
     * @return $this
     */
    public function setMethod($method)
    {
        $this->options->set('method', $method);
        return $this;
    }

    /**
     * 获取当前交易接口方法名
     * @return string
     */
    public function getMethod()
    {
        return $this->options->get('method');
    }

    /**
     * 设置公共参数（透传到 options）
     * @param array $option key-value 公共参数
     * @return Trade
     */
    public function setOption($option = [])
    {
        foreach ($option as $key => $vo) {
            $this->options->set($key, $vo);
        }
        return $this;
    }

    /**
     * 获取当前公共参数
     * @return array|string|null
     */
    public function getOption()
    {
        return $this->options->get();
    }

    /**
     * 执行当前设置的交易接口
     * @param array $options 业务参数（写入 biz_content）
     * @return array|boolean 接口返回结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function apply($options)
    {
        return $this->getResult($options);
    }
}