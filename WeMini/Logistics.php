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

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 小程序物流助手
 * @package WeMini
 */
class Logistics extends BasicWeChat
{
    /**
     * 生成运单
     * @param array $data 运单参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addOrder($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/add?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 取消运单
     * @param array $data 取消参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function cancelOrder($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/cancel?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取快递公司列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getAllDelivery()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/delivery/getall?access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }

    /**
     * 获取运单数据
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getOrder($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查询运单轨迹
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPath($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/path/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取打印员列表（需使用微信打单时调用）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPrinter()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/printer/getall?access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }

    /**
     * 获取电子面单余额（加盟类快递）
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getQuota($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/quota/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 模拟更新订单状态（测试用）
     * @param array $data 模拟参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function testUpdateOrder($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/test_update_order?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 配置面单打印员（微信打单）
     * @param array $data 配置参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updatePrinter($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/printer/update?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取面单联系人信息
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getContact($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/contact/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 预览面单模板（调试用）
     * @param array $data 模板参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function previewTemplate($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/template/preview?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 更新商户审核结果
     * @param array $data 审核结果
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateBusiness($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/service/business/update?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 更新运单轨迹
     * @param array $data 轨迹参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updatePath($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/delivery/path/update?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }


    /**
     * 绑定/解绑物流账号
     * @param array $data 账号参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function bindAccount($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/bind?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }


    /**
     * 获取已绑定的物流账号列表
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getAllAccount($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/account/getall?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 批量获取运单数据
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function batchGetOrder($data)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/express/business/order/batchget?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }


}