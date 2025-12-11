<?php

namespace WePayV3;

use WePayV3\Contracts\BasicWePay;

/**
 * 消费者投诉 2.0
 * @package WePayV3
 */
class Complaints extends BasicWePay
{

    /**
     * 查询投诉列表
     * @param int $offset 分页起始
     * @param int $limit 分页大小
     * @param string $begin_date 开始日期（YYYY-MM-DD）
     * @param string $end_date 结束日期（YYYY-MM-DD）
     * @return array|string 投诉列表
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function complaintList($offset, $limit, $begin_date, $end_date)
    {
        $mchId = $this->config['mch_id'];
        $pathinfo = "/v3/merchant-service/complaints-v2?limit={$limit}&offset={$offset}&begin_date={$begin_date}&end_date={$end_date}&complainted_mchid={$mchId}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 查询投诉详情
     * @param string $complaint_id 投诉单号
     * @return array|string 投诉详情
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function complaintDetails($complaint_id)
    {
        $pathinfo = "/v3/merchant-service/complaints-v2/{$complaint_id}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 查询投诉协商历史
     * @param string $complaint_id 投诉单号
     * @return array|string 协商记录
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function negotiationHistory($complaint_id)
    {
        $pathinfo = "/v3/merchant-service/complaints-v2/{$complaint_id}/negotiation-historys";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 创建投诉通知回调地址
     * @param string $url 回调地址
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function CreateComplaintsNotify($url)
    {
        return $this->doRequest('POST', '/v3/merchant-service/complaint-notifications', json_encode(['url' => $url], JSON_UNESCAPED_UNICODE), true);

    }

    /**
     * 查询投诉通知回调地址
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function queryComplaintsNotify()
    {
        return $this->doRequest('GET', '/v3/merchant-service/complaint-notifications', '', true);

    }

    /**
     * 更新投诉通知回调地址
     * @param string $url 回调地址
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function updateComplaintsNotify($url)
    {
        return $this->doRequest('PUT', '/v3/merchant-service/complaint-notifications', json_encode(['url' => $url], JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 删除投诉通知回调地址
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function deleteComplaintsNotify()
    {
        return $this->doRequest('DELETE', '/v3/merchant-service/complaint-notifications', '', true);
    }

    /**
     * 回复投诉
     * @param string $complaint_id 投诉单号
     * @param array $content 回复内容（含跳转链接、文本等）
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function replyInfo($complaint_id, array $content)
    {
        $content['complainted_mchid'] = $this->config['mch_id'];
        $pathinfo = "/v3/merchant-service/complaints-v2/{$complaint_id}/response";
        return $this->doRequest('POST', $pathinfo, json_encode($content, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 标记投诉处理完成
     * @param string $complaint_id 投诉单号
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function completeComplaints($complaint_id)
    {
        $mchId = $this->config['mch_id'];
        $pathinfo = "/v3/merchant-service/complaints-v2/{$complaint_id}/complete";
        return $this->doRequest('POST', $pathinfo, json_encode(['complainted_mchid' => $mchId], JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 下载投诉图片
     * @param string $pathinfo 文件路径（接口返回的下载地址 path）
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function downLoadImg($pathinfo)
    {
        return $this->doRequest('GET', $pathinfo, '', true, false);
    }

    /**
     * 上传投诉相关图片
     * @param array $imginfo 图片上传参数（文件流对应的 media 字段等）
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function uploadImg(array $imginfo)
    {
        return $this->doRequest('POST', '/v3/merchant-service/images/upload', json_encode($imginfo, JSON_UNESCAPED_UNICODE), true);
    }
}