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
 * 卡券管理
 * @package WeChat
 */
class Card extends BasicWeChat
{
    /**
     * 创建卡券
     * @param array $data 卡券数据（card 对象）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function create(array $data)
    {
        $url = "https://api.weixin.qq.com/card/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 设置买单开关
     * @param string $cardId 卡券ID
     * @param bool $isOpen 是否开启
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setPaycell($cardId, $isOpen = true)
    {
        $url = "https://api.weixin.qq.com/card/paycell/set?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'is_open' => $isOpen]);
    }

    /**
     * 设置自助核销
     * @param string $cardId 卡券ID
     * @param bool $isOpen 是否开启
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setConsumeCell($cardId, $isOpen = true)
    {
        $url = "https://api.weixin.qq.com/card/selfconsumecell/set?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'is_open' => $isOpen]);
    }

    /**
     * 创建卡券二维码
     * @param array $data 二维码参数（action_name, card 等）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function createQrc(array $data)
    {
        $url = "https://api.weixin.qq.com/card/qrcode/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 创建卡券货架
     * @param array $data 货架参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function createLandingPage(array $data)
    {
        $url = "https://api.weixin.qq.com/card/landingpage/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 导入自定义 code
     * @param string $cardId 卡券ID
     * @param array $code code 列表（最多10万）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function deposit($cardId, array $code)
    {
        $url = "https://api.weixin.qq.com/card/code/deposit?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'code' => $code]);
    }

    /**
     * 查询已导入 code 数量
     * @param string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getDepositCount($cardId)
    {
        $url = "https://api.weixin.qq.com/card/code/getdepositcount?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId]);
    }

    /**
     * 校验导入的 code
     * @param string $cardId 卡券ID
     * @param array $code 自定义 code 列表（<=100）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function checkCode($cardId, array $code)
    {
        $url = "https://api.weixin.qq.com/card/code/checkcode?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'code' => $code]);
    }

    /**
     * 获取图文群发卡券的 HTML
     * @param string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getNewsHtml($cardId)
    {
        $url = "https://api.weixin.qq.com/card/mpnews/gethtml?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId]);
    }

    /**
     * 设置测试白名单
     * @param array $openids 测试 openid 列表
     * @param array $usernames 测试微信号列表
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setTestWhiteList($openids = [], $usernames = [])
    {
        $url = "https://api.weixin.qq.com/card/testwhitelist/set?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['openid' => $openids, 'username' => $usernames]);
    }

    /**
     * 查询 Code 状态
     * @param string $code 卡券 code
     * @param string $cardId 卡券ID（自定义 code 必填）
     * @param bool $checkConsume 是否校验核销状态
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCode($code, $cardId = null, $checkConsume = null)
    {
        $data = ['code' => $code];
        is_null($cardId) || $data['card_id'] = $cardId;
        is_null($checkConsume) || $data['check_consume'] = $checkConsume;
        $url = "https://api.weixin.qq.com/card/code/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 核销 Code
     * @param string $code 待核销的 code
     * @param null|string $card_id 卡券ID（自定义 code 必填）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function consume($code, $card_id = null)
    {
        $data = ['code' => $code];
        is_null($card_id) || $data['card_id'] = $card_id;
        $url = "https://api.weixin.qq.com/card/code/consume?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 解码加密 Code
     * @param string $encryptCode 加密 code
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function decrypt($encryptCode)
    {
        $url = "https://api.weixin.qq.com/card/code/decrypt?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['encrypt_code' => $encryptCode]);
    }

    /**
     * 获取用户已领取卡券
     * @param string $openid 用户 openid
     * @param null|string $cardId 卡券ID（可选）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardList($openid, $cardId = null)
    {
        $data = ['openid' => $openid];
        is_null($cardId) || $data['card_id'] = $cardId;
        $url = "https://api.weixin.qq.com/card/user/getcardlist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查看卡券详情
     * @param string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCard($cardId)
    {
        $url = "https://api.weixin.qq.com/card/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId]);
    }

    /**
     * 批量查询卡券
     * @param int $offset 起始偏移量
     * @param int $count 拉取数量（<=50）
     * @param array $statusList 状态过滤
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchGet($offset, $count = 50, array $statusList = [])
    {
        $data = ['offset' => $offset, 'count' => $count];
        empty($statusList) || $data['status_list'] = $statusList;
        $url = "https://api.weixin.qq.com/card/batchget?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 更新会员卡信息
     * @param string $cardId 卡券ID
     * @param array $memberCard 会员卡内容
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function updateCard($cardId, array $memberCard)
    {
        $url = "https://api.weixin.qq.com/card/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'member_card' => $memberCard]);
    }

    /**
     * 修改库存
     * @param string $card_id 卡券ID
     * @param int|null $increase_stock_value 增加库存
     * @param int|null $reduce_stock_value 减少库存
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function modifyStock($card_id, $increase_stock_value = null, $reduce_stock_value = null)
    {
        $data = ['card_id' => $card_id];
        is_null($reduce_stock_value) || $data['reduce_stock_value'] = $reduce_stock_value;
        is_null($increase_stock_value) || $data['increase_stock_value'] = $increase_stock_value;
        $url = "https://api.weixin.qq.com/card/modifystock?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 变更 Code
     * @param string $code 原 code
     * @param string $new_code 新 code
     * @param null|string $card_id 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function updateCode($code, $new_code, $card_id = null)
    {
        $data = ['code' => $code, 'new_code' => $new_code];
        is_null($card_id) || $data['card_id'] = $card_id;
        $url = "https://api.weixin.qq.com/card/code/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 删除卡券
     * @param string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function deleteCard($cardId)
    {
        $url = "https://api.weixin.qq.com/card/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId]);
    }

    /**
     * 设置卡券失效
     * @param string $code 卡券 code
     * @param string $cardId 卡券ID
     * @param null|string $reason 失效原因
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function unAvailable($code, $cardId, $reason = null)
    {
        $data = ['code' => $code, 'card_id' => $cardId];
        is_null($reason) || $data['reason'] = $reason;
        $url = "https://api.weixin.qq.com/card/code/unavailable?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 拉取卡券概况数据
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期
     * @param string $condSource 卡券来源 0|1
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardBizuininfo($beginDate, $endDate, $condSource)
    {
        $url = "https://api.weixin.qq.com/datacube/getcardbizuininfo?access_token=ACCESS_TOKEN";
        $data = ['begin_date' => $beginDate, 'end_date' => $endDate, 'cond_source' => $condSource];
        return $this->callPostApi($url, $data);
    }

    /**
     * 获取免费券数据
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期
     * @param int $condSource 卡券来源 0|1
     * @param null|string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardCardinfo($beginDate, $endDate, $condSource, $cardId = null)
    {
        $url = "https://api.weixin.qq.com/datacube/getcardcardinfo?access_token=ACCESS_TOKEN";
        $data = ['begin_date' => $beginDate, 'end_date' => $endDate, 'cond_source' => $condSource];
        is_null($cardId) || $data['card_id'] = $cardId;
        return $this->callPostApi($url, $data);
    }


    /**
     * 激活会员卡
     * @param array $data 激活参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function activateMemberCard(array $data)
    {
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data);
    }

    /**
     * 设置开卡字段（激活表单）
     * @param array $data 表单字段
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function setActivateMemberCardUser(array $data)
    {
        $url = 'https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data);
    }

    /**
     * 获取用户提交的激活资料
     * @param string $activateTicket 激活 ticket
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getActivateMemberCardTempinfo($activateTicket)
    {
        $url = 'https://api.weixin.qq.com/card/membercard/activatetempinfo/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['activate_ticket' => $activateTicket]);
    }

    /**
     * 更新会员信息
     * @param array $data 更新参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function updateMemberCardUser(array $data)
    {
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data);
    }

    /**
     * 拉取会员卡概况数据
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期
     * @param string $condSource 卡券来源 0|1
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardMemberCardinfo($beginDate, $endDate, $condSource)
    {
        $url = "https://api.weixin.qq.com/datacube/getcardmembercardinfo?access_token=ACCESS_TOKEN";
        $data = ['begin_date' => $beginDate, 'end_date' => $endDate, 'cond_source' => $condSource];
        return $this->callPostApi($url, $data);
    }

    /**
     * 拉取单张会员卡数据
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期
     * @param string $cardId 卡券ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardMemberCardDetail($beginDate, $endDate, $cardId)
    {
        $url = "https://api.weixin.qq.com/datacube/getcardmembercarddetail?access_token=ACCESS_TOKEN";
        $data = ['begin_date' => $beginDate, 'end_date' => $endDate, 'card_id' => $cardId];
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询会员信息（积分）
     * @param string $cardId 会员卡ID
     * @param string $code 用户 code
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCardMemberCard($cardId, $code)
    {
        $data = ['card_id' => $cardId, 'code' => $code];
        $url = "https://api.weixin.qq.com/card/membercard/userinfo/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 支付后投放卡券规则
     * @param array $data 规则参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payGiftCard(array $data)
    {
        $url = "https://api.weixin.qq.com/card/paygiftcard/add?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 删除支付后投放卡券规则
     * @param int $ruleId 规则ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function delPayGiftCard($ruleId)
    {
        $url = "https://api.weixin.qq.com/card/paygiftcard/add?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['rule_id' => $ruleId]);
    }

    /**
     * 查询支付后投放卡券规则
     * @param int $ruleId 规则ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getPayGiftCard($ruleId)
    {
        $url = "https://api.weixin.qq.com/card/paygiftcard/getbyid?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['rule_id' => $ruleId]);
    }

    /**
     * 批量查询支付后投放规则
     * @param int $offset 起始偏移
     * @param int $count 数量
     * @param bool $effective 是否仅查询生效规则
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchGetPayGiftCard($offset = 0, $count = 10, $effective = true)
    {
        $url = "https://api.weixin.qq.com/card/paygiftcard/batchget?access_token=ACCESS_TOKEN";
        $data = ['type' => 'RULE_TYPE_PAY_MEMBER_CARD', 'offset' => $offset, 'count' => $count, 'effective' => $effective];
        return $this->callPostApi($url, $data);
    }

    /**
     * 创建支付后立减金活动
     * @param array $data 活动参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function addActivity(array $data)
    {
        $url = "https://api.weixin.qq.com/card/mkt/activity/create?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 开通券点账户
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payActivate()
    {
        $url = "https://api.weixin.qq.com/card/pay/activate?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 预估优惠券库存价格
     * @param string $cardId 卡券ID
     * @param int $quantity 兑换数量
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getPayprice($cardId, $quantity)
    {
        $url = "POST https://api.weixin.qq.com/card/pay/getpayprice?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['card_id' => $cardId, 'quantity' => $quantity]);
    }

    /**
     * 查询券点余额
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getCoinsInfo()
    {
        $url = "https://api.weixin.qq.com/card/pay/getcoinsinfo?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 确认兑换库存
     * @param string $cardId 卡券ID
     * @param int $quantity 数量
     * @param string $orderId 批价返回的订单号
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payConfirm($cardId, $quantity, $orderId)
    {
        $url = "https://api.weixin.qq.com/card/pay/confirm?access_token=ACCESS_TOKEN";
        $data = ['card_id' => $cardId, 'quantity' => $quantity, 'order_id' => $orderId];
        return $this->callPostApi($url, $data);
    }

    /**
     * 充值券点
     * @param int $coinCount 充值数量
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payRecharge($coinCount)
    {
        $url = "https://api.weixin.qq.com/card/pay/recharge?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['coin_count' => $coinCount]);
    }

    /**
     * 查询券点订单详情
     * @param string $orderId 订单号
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payGetOrder($orderId)
    {
        $url = "https://api.weixin.qq.com/card/pay/getorder?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['order_id' => $orderId]);
    }

    /**
     * 查询券点流水
     * @param array $data 查询参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function payGetList(array $data)
    {
        $url = "https://api.weixin.qq.com/card/pay/getorderlist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 获取开卡插件参数
     * @param array $data 入口参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getActivateUrl(array $data)
    {
        $url = "https://api.weixin.qq.com/card/membercard/activate/geturl?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }
}