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
 * 小程序地址管理
 * @package WeMini
 */
class Poi extends BasicWeChat
{
    /**
     * 添加附近地点
     * @param string $related_name 经营主体
     * @param string $related_credential 证件号
     * @param string $related_address 地址
     * @param string $related_proof_material 证明材料 media_id
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addBearByPoi($related_name, $related_credential, $related_address, $related_proof_material)
    {
        $url = 'https://api.weixin.qq.com/wxa/addnearbypoi?access_token=ACCESS_TOKEN';
        $data = [
            'related_name'    => $related_name, 'related_credential' => $related_credential,
            'related_address' => $related_address, 'related_proof_material' => $related_proof_material,
        ];
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 查看地点列表
     * @param int $page 页码从1开始
     * @param int $page_rows 每页数量(<=1000)
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getNearByPoiList($page = 1, $page_rows = 1000)
    {
        $url = "https://api.weixin.qq.com/wxa/getnearbypoilist?page={$page}&page_rows={$page_rows}&access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 删除附近地点
     * @param string $poi_id 地点ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function delNearByPoiList($poi_id)
    {
        $url = "https://api.weixin.qq.com/wxa/delnearbypoi?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['poi_id' => $poi_id], true);
    }

    /**
     * 设置附近小程序展示
     * @param string $poi_id 地点ID
     * @param string $status 0 取消 | 1 展示
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function setNearByPoiShowStatus($poi_id, $status)
    {
        $url = "https://api.weixin.qq.com/wxa/setnearbypoishowstatus?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['poi_id' => $poi_id, 'status' => $status], true);
    }
}