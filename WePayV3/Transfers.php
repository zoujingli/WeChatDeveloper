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

namespace WePayV3;

use WePayV3\Contracts\BasicWePay;

/**
 * 商家转账到零钱 (V3)
 * @package WePayV3
 */
class Transfers extends BasicWePay
{

    /**
     * 商家转账到零钱（单笔）
     * @param array $body 转账参数（out_bill_no, transfer_amount, openid/user_name 等，敏感字段需 RSA）
     * @return array|string
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @link https://pay.weixin.qq.com/doc/v3/merchant/4012716434
     */
    public function bills($body)
    {
        if (empty($body['appid'])) {
            $body['appid'] = $this->config['appid'];
        }
        if (!empty($body['user_name'])) {
            $body['user_name'] = $this->rsaEncode($body['user_name']);
        }
        return $this->doRequest('POST', '/v3/fund-app/mch-transfer/transfer-bills', json_encode($body, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 查询转账结果
     * @param string $out_bill_no 商户转账单号
     * @return array|string 转账状态
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function billsQuery($out_bill_no)
    {
        return $this->doRequest('GET', "/v3/fund-app/mch-transfer/transfer-bills/out-bill-no/{$out_bill_no}", '', true);
    }

    /**
     * 发起商家批量转账
     * @param array $body 批量参数（out_batch_no, transfer_detail_list 等，姓名需 RSA）
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @link https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml
     */
    public function batchs($body)
    {
        if (empty($body['appid'])) {
            $body['appid'] = $this->config['appid'];
        }
        if (isset($body['transfer_detail_list']) && is_array($body['transfer_detail_list'])) {
            foreach ($body['transfer_detail_list'] as &$item) if (isset($item['user_name'])) {
                $item['user_name'] = $this->rsaEncode($item['user_name']);
            }
            if (empty($body['total_num'])) {
                $body['total_num'] = count($body['transfer_detail_list']);
            }
            if (empty($body['total_amount'])) {
                $body['total_amount'] = array_sum(array_column($body['transfer_detail_list'], 'transfer_amount'));
            }
        }
        return $this->doRequest('POST', '/v3/transfer/batches', json_encode($body, JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 查询批量转账批次
     * @param string $batchId 微信批次单号（二选一）
     * @param string $outBatchNo 商户批次单号（二选一）
     * @param bool $needQueryDetail 是否拉取明细
     * @param int $offset 明细起始位置
     * @param int $limit 最大明细条数
     * @param string $detailStatus 明细状态 ALL|SUCCESS|FAIL
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

    /**
     * 通过微信明细单号查询明细
     * @param string $batchId 微信批次单号
     * @param string $detailId 微信明细单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function detailBatchId($batchId, $detailId)
    {
        $pathinfo = "/v3/transfer/batches/batch-id/{$batchId}/details/detail-id/{$detailId}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }

    /**
     * 通过商家明细单号查询明细
     * @param string $outBatchNo 商户批次单号
     * @param string $outDetailNo 商户明细单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function detailOutBatchNo($outBatchNo, $outDetailNo)
    {
        $pathinfo = "/v3/transfer/batches/out-batch-no/{$outBatchNo}/details/out-detail-no/{$outDetailNo}";
        return $this->doRequest('GET', $pathinfo, '', true);
    }
}
