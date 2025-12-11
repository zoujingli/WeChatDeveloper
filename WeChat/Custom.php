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
 * 客服消息处理
 * @package WeChat
 */
class Custom extends BasicWeChat
{
    /**
     * 添加客服帐号
     * @param string $kf_account 客服账号，格式 账号前缀@公众号微信号
     * @param string $nickname 客服昵称
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function addAccount($kf_account, $nickname)
    {
        $url = "https://api.weixin.qq.com/customservice/kfaccount/add?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['kf_account' => $kf_account, 'nickname' => $nickname]);
    }

    /**
     * 修改客服帐号
     * @param string $kfAccount 客服账号
     * @param string $nickname 客服昵称
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function updateAccount($kfAccount, $nickname)
    {
        $url = "https://api.weixin.qq.com/customservice/kfaccount/update?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['kf_account' => $kfAccount, 'nickname' => $nickname]);
    }

    /**
     * 删除客服帐号
     * @param string $kfAccount 客服账号
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function deleteAccount($kfAccount)
    {
        $url = "https://api.weixin.qq.com/customservice/kfaccount/del?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['kf_account' => $kfAccount]);
    }

    /**
     * 邀请绑定客服帐号
     * @param string $kfAccount 客服账号，格式 账号前缀@公众号微信号
     * @param string $invite_wx 接收绑定邀请的客服微信号
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function inviteWorker($kfAccount, $invite_wx)
    {
        $url = 'https://api.weixin.qq.com/customservice/kfaccount/inviteworker?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['kf_account' => $kfAccount, 'invite_wx' => $invite_wx]);
    }

    /**
     * 获取客服账号列表
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getAccountList()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/customservice/getkflist?access_token=ACCESS_TOKEN";
        return $this->callGetApi($url);
    }

    /**
     * 设置客服头像
     * @param string $kf_account 客服账号
     * @param string $image 本地图片路径
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function uploadHeadimg($kf_account, $image)
    {
        $url = "https://api.weixin.qq.com/customservice/kfaccount/uploadheadimg?access_token=ACCESS_TOKEN&kf_account={$kf_account}";
        return $this->callPostApi($url, ['media' => Tools::createCurlFile($image)], false);
    }

    /**
     * 发送客服消息
     * @param array $data 消息体（touser, msgtype, content 等）
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function send(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 设置客服输入状态
     * @param string $openid 用户 openid
     * @param string $command Typing|CancelTyping
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function typing($openid, $command = 'Typing')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/typing?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['touser' => $openid, 'command' => $command]);
    }

    /**
     * 根据标签群发
     * @param array $data 群发参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massSendAll(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 根据 OpenID 列表群发
     * @param array $data 群发参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massSend(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 删除群发
     * @param int $msg_id 群发消息ID
     * @param null|int $article_idx 图文位置，0 删除全部
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massDelete($msg_id, $article_idx = null)
    {
        $data = ['msg_id' => $msg_id];
        is_null($article_idx) || $data['article_idx'] = $article_idx;
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/delete?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 群发预览
     * @param array $data 预览参数
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massPreview(array $data)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, $data);
    }

    /**
     * 查询群发状态
     * @param int $msgId 群发消息ID
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massGet($msgId)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['msg_id' => $msgId]);
    }

    /**
     * 获取群发速度
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massGetSeed()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/speed/get?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, []);
    }

    /**
     * 设置群发速度
     * @param int $speed 速度级别
     * @return array
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function massSetSeed($speed)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/speed/set?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['speed' => $speed]);
    }
}