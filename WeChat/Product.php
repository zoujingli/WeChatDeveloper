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
 * 商店管理
 * @package WeChat
 */
class Product extends BasicWeChat
{
    /**
     * 提交审核/取消发布商品
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @param string $status on 提交审核 | off 取消发布
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function modStatus($keystandard, $keystr, $status = 'on')
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
    public function setTestWhiteList(array $openids = [], array $usernames = [])
    {
        $url = "https://api.weixin.qq.com/scan/testwhitelist/set?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['openid' => $openids, 'username' => $usernames]);
    }

    /**
     * 获取商品二维码
     * @param string $keystandard 编码标准
     * @param string $keystr 编码内容
     * @param int $qrcode_size 边长像素，默认100
     * @param array $extinfo 自定义扩展
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getQrcode($keystandard, $keystr, $qrcode_size, $extinfo = [])
    {
        $url = "https://api.weixin.qq.com/scan/product/getqrcode?access_token=ACCESS_TOKEN";
        $data = ['keystandard' => $keystandard, 'keystr' => $keystr, 'qrcode_size' => $qrcode_size];
        empty($extinfo) || $data['extinfo'] = $extinfo;
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
    public function getProduct($keystandard, $keystr)
    {
        $url = "https://api.weixin.qq.com/scan/product/get?access_token=ACCESS_TOKEN";
        $data = ['keystandard' => $keystandard, 'keystr' => $keystr];
        empty($extinfo) || $data['extinfo'] = $extinfo;
        return $this->callPostApi($url, $data);
    }

    /**
     * 批量查询商品信息
     * @param int $offset 起始位置
     * @param int $limit 数量
     * @param null|string $status on|off|check|reject|all
     * @param string $keystr 模糊编码过滤
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getProductList($offset, $limit = 10, $status = null, $keystr = '')
    {
        $url = "https://api.weixin.qq.com/scan/product/get?access_token=ACCESS_TOKEN";
        $data = ['offset' => $offset, 'limit' => $limit];
        is_null($status) || $data['status'] = $status;
        empty($keystr) || $data['keystr'] = $keystr;
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
    public function scanTicketCheck($ticket)
    {
        $url = "https://api.weixin.qq.com/scan/scanticket/check?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['ticket' => $ticket]);
    }

    /**
     * 清除扫码记录
     * @param string $keystandard 商品编码标准
     * @param string $keystr 商品编码内容
     * @param string $extinfo 获取二维码时的 extinfo
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function clearScanticket($keystandard, $keystr, $extinfo)
    {
        $url = "https://api.weixin.qq.com/scan/scanticket/check?access_token=ACCESS_TOKEN";
        $data = ['keystandard' => $keystandard, 'keystr' => $keystr, 'extinfo' => $extinfo];
        return $this->callPostApi($url, $data);
    }
}