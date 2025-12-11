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
use WeChat\Contracts\Tools;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;
use WXBizDataCrypt;


/**
 * 小程序数据加解密
 * @package WeMini
 */
class Crypt extends BasicWeChat
{

    /**
     * 通过 code 解密用户信息
     * @param string $code 登录凭证
     * @param string $iv 初始向量
     * @param string $encryptedData 加密数据
     * @return array
     * @throws \WeChat\Exceptions\InvalidDecryptException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function userInfo($code, $iv, $encryptedData)
    {
        $result = $this->session($code);
        if (empty($result['session_key'])) {
            throw new InvalidResponseException('Code 换取 SessionKey 失败', 403);
        }
        $userinfo = $this->decode($iv, $result['session_key'], $encryptedData);
        if (empty($userinfo)) {
            throw new InvalidDecryptException('用户信息解析失败', 403);
        }
        return array_merge($result, $userinfo);
    }

    /**
     * code 换取 session_key
     * @param string $code 登录 code
     * @return array
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function session($code)
    {
        $appid = $this->config->get('appid');
        $secret = $this->config->get('appsecret');
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid={$appid}&secret={$secret}&js_code={$code}&grant_type=authorization_code";
        return json_decode(Tools::get($url), true);
    }

    /**
     * 解密数据
     * @param string $iv 初始向量
     * @param string $sessionKey 会话密钥
     * @param string $encryptedData 加密数据
     * @return bool|array
     */
    public function decode($iv, $sessionKey, $encryptedData)
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'crypt' . DIRECTORY_SEPARATOR . 'wxBizDataCrypt.php';
        $pc = new WXBizDataCrypt($this->config->get('appid'), $sessionKey);
        $data = '';
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return json_decode($data, true);
        }
        return false;
    }

    /**
     * 通过 code 获取手机号
     * @param string $code 授权码
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPhoneNumber($code)
    {
        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=ACCESS_TOKEN';
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->httpPostForJson($url, ['code' => $code], true);
    }

    /**
     * 支付后获取用户 UnionId
     * @param string $openid 用户 openid
     * @param null|string $transaction_id 微信支付订单号
     * @param null|string $mch_id 商户号
     * @param null|string $out_trade_no 商户订单号
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getPaidUnionId($openid, $transaction_id = null, $mch_id = null, $out_trade_no = null)
    {
        $url = "https://api.weixin.qq.com/wxa/getpaidunionid?access_token=ACCESS_TOKEN&openid={$openid}";
        if (!is_null($mch_id)) $url .= "&mch_id={$mch_id}";
        if (!is_null($out_trade_no)) $url .= "&out_trade_no={$out_trade_no}";
        if (!is_null($transaction_id)) $url .= "&transaction_id={$transaction_id}";
        $this->registerApi($url, __FUNCTION__, func_get_args());
        return $this->callGetApi($url);
    }
}