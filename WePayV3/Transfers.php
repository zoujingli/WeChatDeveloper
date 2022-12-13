<?php

namespace WePayV3;

use WePayV3\Contracts\BasicWePay;

/**
 * 普通商户商家转账到零钱
 * Class Transfers
 * @package WePayV3
 */
class Transfers extends BasicWePay
{
    /**
     * 发起商家批量转账
     * @param array $body
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function batchs($body)
    {
        return $this->doRequest('POST', '/v3/transfer/batches', json_encode($body, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 通过微信批次单号查询批次单
     * @param string $batchId 微信批次单号(二选一)
     * @param string $outBatchNo 商家批次单号(二选一)
     * @param boolean $needQueryDetail 查询指定状态
     * @param integer $offset 请求资源的起始位置
     * @param integer $limit 最大明细条数
     * @param string $detailStatus 查询指定状态
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function query($batchId = '', $outBatchNo = '', $needQueryDetail = true, $offset = 0, $limit = 20, $detailStatus = 'ALL')
    {
        if (empty($batchId)) {
            $pathinfo = "/v3/transfer/batches/out-batch-no/{$outBatchNo}";
        } else {
            $pathinfo = "/v3/transfer/batches/batch-id/{$batchId}";
        }
        $params = http_build_query([
            'limit'             => $limit,
            'offset'            => $offset,
            'detail_status'     => $detailStatus,
            'need_query_detail' => $needQueryDetail ? 'true' : 'false',
        ]);
        return $this->doRequest('GET', "{$pathinfo}?{$params}", '', true);
    }
}