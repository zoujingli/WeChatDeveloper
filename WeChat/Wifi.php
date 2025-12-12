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

namespace WeChat;

use WeChat\Contracts\BasicWeChat;

/**
 * 门店 WIFI 管理
 * @package WeChat
 */
class Wifi extends BasicWeChat
{

    /**
     * 获取 Wi-Fi 门店列表
     * @param int $pageindex 页码（从1开始）
     * @param int $pagesize 每页数量（<=20）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getShopList($pageindex = 1, $pagesize = 2)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/shop/list?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['pageindex' => $pageindex, 'pagesize' => $pagesize]);
    }

    /**
     * 查询门店 Wi-Fi 信息
     * @param int $shop_id 门店ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getShopWifi($shop_id)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/shop/list?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id]);
    }

    /**
     * 修改门店 Wi-Fi
     * @param int $shop_id 门店ID
     * @param string $old_ssid 原 SSID
     * @param string $ssid 新 SSID
     * @param string $password 可选密码
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function upShopWifi($shop_id, $old_ssid, $ssid, $password = null)
    {
        $data = ['shop_id' => $shop_id, 'old_ssid' => $old_ssid, 'ssid' => $ssid];
        is_null($password) || $data['password'] = $password;
        $url = 'https://api.weixin.qq.com/bizwifi/shop/update?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 清空门店网络与设备
     * @param int $shop_id 门店ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function clearShopWifi($shop_id)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/shop/clean?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id]);
    }

    /**
     * 添加密码型设备
     * @param int $shop_id 门店ID
     * @param string $ssid SSID
     * @param null|string $password 密码
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function addShopWifi($shop_id, $ssid, $password = null)
    {
        $data = ['shop_id' => $shop_id, 'ssid' => $ssid, 'password' => $password];
        $url = 'https://api.weixin.qq.com/bizwifi/device/add?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 添加 portal 型设备
     * @param int $shop_id 门店ID
     * @param string $ssid SSID
     * @param bool $reset 是否重置 secretkey
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function addShopPortal($shop_id, $ssid, $reset = false)
    {
        $data = ['shop_id' => $shop_id, 'ssid' => $ssid, 'reset' => $reset];
        $url = 'https://api.weixin.qq.com/bizwifi/apportal/register?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 查询设备
     * @param int|null $shop_id 门店ID
     * @param int|null $pageindex 页码
     * @param int|null $pagesize 数量
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function queryShopWifi($shop_id = null, $pageindex = null, $pagesize = null)
    {
        $data = [];
        is_null($pagesize) || $data['pagesize'] = $pagesize;
        is_null($pageindex) || $data['pageindex'] = $pageindex;
        is_null($shop_id) || $data['shop_id'] = $shop_id;
        $url = 'https://api.weixin.qq.com/bizwifi/device/list?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 删除设备
     * @param string $bssid 设备 MAC，冒号分隔小写
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function delShopWifi($bssid)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/device/delete?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['bssid' => $bssid]);
    }

    /**
     * 获取物料二维码
     * @param int $shop_id 门店ID
     * @param string $ssid SSID
     * @param int $img_id 物料样式 0|1
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getQrc($shop_id, $ssid, $img_id = 1)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/qrcode/get?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id, 'ssid' => $ssid, 'img_id' => $img_id]);
    }

    /**
     * 设置商家主页
     * @param int $shop_id 门店ID
     * @param int $template_id 0 默认 | 1 自定义链接
     * @param null|string $url 自定义链接（template_id=1 必填）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setHomePage($shop_id, $template_id, $url = null)
    {
        $data = ['shop_id' => $shop_id, 'template_id' => $template_id];
        !is_null($url) && $data['struct'] = ['url' => $url];
        $url = 'https://api.weixin.qq.com/bizwifi/homepage/set?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 查询商家主页
     * @param int $shop_id 门店ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getHomePage($shop_id)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/homepage/get?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id]);
    }

    /**
     * 设置微信首页欢迎语
     * @param int $shop_id 门店ID
     * @param int $bar_type 0|1|2|3
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setBar($shop_id, $bar_type = 1)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/bar/set?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id, 'bar_type' => $bar_type]);
    }

    /**
     * 设置连网完成页
     * @param int $shop_id 门店ID
     * @param string $finishpage_url 完成页 URL
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setFinishPage($shop_id, $finishpage_url)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/finishpage/set?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id, 'finishpage_url' => $finishpage_url]);
    }

    /**
     * Wi-Fi 数据统计
     * @param string $begin_date 开始日期 yyyy-mm-dd
     * @param string $end_date 结束日期 yyyy-mm-dd
     * @param int $shop_id 门店ID，-1 总统计
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function staticList($begin_date, $end_date, $shop_id = -1)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/statistics/list?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id, 'begin_date' => $begin_date, 'end_date' => $end_date]);
    }

    /**
     * 设置门店卡券投放
     * @param int $shop_id 门店ID，0 表示全部
     * @param int $card_id 卡券ID
     * @param string $card_describe 描述（<=18 字符）
     * @param string $start_time 开始时间戳
     * @param string $end_time 结束时间戳
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setCouponput($shop_id, $card_id, $card_describe, $start_time, $end_time)
    {
        $data = ['shop_id' => $shop_id, 'card_id' => $card_id, 'card_describe' => $card_describe, 'start_time' => $start_time, 'end_time' => $end_time];
        $url = 'https://api.weixin.qq.com/bizwifi/couponput/set?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, $data);
    }

    /**
     * 查询门店卡券投放
     * @param int $shop_id 门店ID，0 表示全部
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCouponput($shop_id)
    {
        $url = 'https://api.weixin.qq.com/bizwifi/couponput/get?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['shop_id' => $shop_id]);
    }

}