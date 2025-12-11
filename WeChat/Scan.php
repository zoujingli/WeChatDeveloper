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
 * 扫一扫接入管理
 * @package WeChat
 */
class Scan extends BasicWeChat
{
    /**
     * 获取商户信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getMerchantInfo()
    {
        $url = "https://api.weixin.qq.com/scan/merchantinfo/get?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 创建商品
     * @param array $data 商品数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addProduct(array $data)
    {
        $url = "https://api.weixin.qq.com/scan/product/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 商品发布/取消
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @param string $status on 提交审核 | off 取消
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function modProduct($keystandard, $keystr, $status = 'on')
    {
        $url = "https://api.weixin.qq.com/scan/product/modstatus?access_token=ACCESS_TOKEN";
        $data = ['keystandard' => $keystandard, 'keystr' => $keystr, 'status' => $status];
        return $this->callPostApi($url, $data);
    }

    /**
     * 设置测试人员白名单
     * @param array $openids openid 列表
     * @param array $usernames 微信号列表
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setTestWhiteList($openids = [], $usernames = [])
    {
        $url = "https://api.weixin.qq.com/scan/product/modstatus?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['openid' => $openids, 'username' => $usernames]);
    }

    /**
     * 获取商品二维码
     * @param string $keystandard 编码标准
     * @param string $keystr 编码内容
     * @param null|string $extinfo 自定义标识
     * @param int $qrcode_size 边长像素
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getQrc($keystandard, $keystr, $extinfo = null, $qrcode_size = 64)
    {
        $url = "https://api.weixin.qq.com/scan/product/getqrcode?access_token=ACCESS_TOKEN";
        $data = ['keystandard' => $keystandard, 'keystr' => $keystr, 'qrcode_size' => $qrcode_size];
        is_null($extinfo) || $data['extinfo'] = $extinfo;
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询商品信息
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getProductInfo($keystandard, $keystr)
    {
        $url = "https://api.weixin.qq.com/scan/product/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['keystandard' => $keystandard, 'keystr' => $keystr]);
    }

    /**
     * 批量查询商品信息
     * @param int $offset 起始位置
     * @param int $limit 数量
     * @param string $status on|off|check|reject|all
     * @param string $keystr 关键词过滤
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getProductList($offset = 1, $limit = 10, $status = null, $keystr = null)
    {
        $url = "https://api.weixin.qq.com/scan/product/getlist?access_token=ACCESS_TOKEN";
        $data = ['offset' => $offset, 'limit' => $limit];
        is_null($status) || $data['status'] = $status;
        is_null($keystr) || $data['keystr'] = $keystr;
        return $this->callPostApi($url, $data);
    }

    /**
     * 更新商品信息
     * @param array $data 商品数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateProduct(array $data)
    {
        $url = "https://api.weixin.qq.com/scan/product/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 清除商品信息
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function clearProduct($keystandard, $keystr)
    {
        $url = "https://api.weixin.qq.com/scan/product/clear?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['keystandard' => $keystandard, 'keystr' => $keystr]);
    }

    /**
     * 检查 wxticket 参数
     * @param string $ticket
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function checkTicket($ticket)
    {
        $url = "https://api.weixin.qq.com/scan/scanticket/check?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['ticket' => $ticket]);
    }

    /**
     * 清除扫码记录
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @param string $extinfo 二维码接口时的 extinfo
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function clearScanTicket($keystandard, $keystr, $extinfo)
    {
        $url = "https://api.weixin.qq.com/scan/scanticket/check?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['keystandard' => $keystandard, 'keystr' => $keystr, 'extinfo' => $extinfo]);
    }
}