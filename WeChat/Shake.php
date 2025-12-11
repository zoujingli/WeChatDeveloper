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
use WeChat\Contracts\Tools;

/**
 * 揺一揺周边
 * @package WeChat
 */
class Shake extends BasicWeChat
{
    /**
     * 申请开通摇一摇
     * @param array $data 资质信息
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function register(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/account/register?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询审核状态
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function auditStatus()
    {
        $url = "https://api.weixin.qq.com/shakearound/account/auditstatus?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 申请设备 ID
     * @param string $quantity 数量（>500 走人工审核）
     * @param string $apply_reason 理由
     * @param null|string $comment 备注
     * @param null|string $poi_id 门店ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createApply($quantity, $apply_reason, $comment = null, $poi_id = null)
    {
        $data = ['quantity' => $quantity, 'apply_reason' => $apply_reason];
        is_null($poi_id) || $data['poi_id'] = $poi_id;
        is_null($comment) || $data['comment'] = $comment;
        $url = "https://api.weixin.qq.com/shakearound/device/applyid?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询设备 ID 申请状态
     * @param int $applyId 批次ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getApplyStatus($applyId)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/applyid?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['apply_id' => $applyId]);
    }

    /**
     * 编辑设备信息
     * @param array $data 设备参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateApply(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 设备绑定门店
     * @param array $data 绑定参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function bindLocation(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/bindlocation?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询设备列表
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function search(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/search?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 创建页面
     * @param array $data 页面参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function createPage(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/page/add?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 编辑页面
     * @param array $data 页面参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updatePage(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/page/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询页面列表
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function searchPage(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/page/search?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 删除页面
     * @param int $pageId 页面ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function deletePage($pageId)
    {
        $url = "https://api.weixin.qq.com/shakearound/page/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['page_id' => $pageId]);
    }

    /**
     * 上传图片素材
     * @param string $filename 图片路径
     * @param string $type icon|license
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function upload($filename, $type = 'icon')
    {
        $url = "https://api.weixin.qq.com/shakearound/material/add?access_token=ACCESS_TOKEN&type={$type}";
        return $this->callPostApi($url, ['media' => Tools::createCurlFile($filename)], false);
    }

    /**
     * 配置设备与页面关联
     * @param array $data 关联参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function bindPage(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/bindpage?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询设备与页面关联
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function queryPage(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/relation/search?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 设备维度数据统计
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function totalDevice(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/statistics/device?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 批量查询设备统计
     * @param int $date 日期时间戳（秒）
     * @param int $pageIndex 页码
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function totalDeviceList($date, $pageIndex = 1)
    {
        $url = "https://api.weixin.qq.com/shakearound/statistics/devicelist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['date' => $date, 'page_index' => $pageIndex]);
    }

    /**
     * 页面维度数据统计
     * @param int $pageId 页面ID
     * @param int $beginDate 起始时间戳
     * @param int $endDate 结束时间戳
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function totalPage($pageId, $beginDate, $endDate)
    {
        $url = "https://api.weixin.qq.com/shakearound/statistics/page?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['page_id' => $pageId, 'begin_date' => $beginDate, 'end_date' => $endDate]);
    }

    /**
     * 编辑分组信息
     * @param int $groupId 分组ID
     * @param string $groupName 分组名称
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateGroup($groupId, $groupName)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['group_id' => $groupId, 'group_name' => $groupName]);
    }

    /**
     * 删除分组
     * @param int $groupId 分组ID
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function deleteGroup($groupId)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['group_id' => $groupId]);
    }

    /**
     * 查询分组列表
     * @param int $begin 起始索引
     * @param int $count 数量（<=1000）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGroupList($begin = 0, $count = 10)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/getlist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['begin' => $begin, 'count' => $count]);
    }


    /**
     * 查询分组详情
     * @param int $group_id 分组ID
     * @param int $begin 设备起始索引
     * @param int $count 数量（<=1000）
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getGroupDetail($group_id, $begin = 0, $count = 100)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/getdetail?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['group_id' => $group_id, 'begin' => $begin, 'count' => $count]);
    }

    /**
     * 分组添加设备
     * @param array $data 设备参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function addDeviceGroup(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/adddevice?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 分组移除设备
     * @param array $data 设备参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function deleteDeviceGroup(array $data)
    {
        $url = "https://api.weixin.qq.com/shakearound/device/group/deletedevice?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }
}