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
 * 微信粉丝管理
 * @package WeChat
 */
class User extends BasicWeChat
{

    /**
     * 设置用户备注名接口
     * @param string $openid 用户openid
     * @param string $remark 备注名（长度不超过30字符）
     * @return array 操作结果
     * @throws Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function updateMark($openid, $remark)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/updateremark?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['openid' => $openid, 'remark' => $remark]);
    }

    /**
     * 获取用户基本信息接口（包括UnionID）
     * @param string $openid 用户openid
     * @param string $lang 返回国家地区语言版本（zh_CN, zh_TW, en）
     * @return array 用户信息（nickname, headimgurl, sex, province, city, country, unionid等）
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getUserInfo($openid, $lang = 'zh_CN')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=ACCESS_TOKEN&openid={$openid}&lang={$lang}";
        return $this->callGetApi($url);
    }

    /**
     * 批量获取用户基本信息接口
     * @param array $openids 用户openid列表（最多100个）
     * @param string $lang 返回国家地区语言版本
     * @return array 用户信息列表
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getBatchUserInfo(array $openids, $lang = 'zh_CN')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token=ACCESS_TOKEN';
        $data = ['user_list' => []];
        foreach ($openids as $openid) {
            $data['user_list'][] = ['openid' => $openid, 'lang' => $lang];
        }
        return $this->callPostApi($url, $data);
    }

    /**
     * 获取用户列表接口
     * @param string $next_openid 第一个拉取的openid，不填默认从头开始拉取
     * @return array 用户列表（total, count, data.openid, next_openid）
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getUserList($next_openid = '')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=ACCESS_TOKEN&next_openid={$next_openid}";
        return $this->callGetApi($url);
    }

    /**
     * 获取标签下粉丝列表接口
     * @param integer $tagid 标签ID
     * @param string $nextOpenid 第一个拉取的openid，不填默认从头开始拉取
     * @return array 粉丝列表（count, data.openid, next_openid）
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getUserListByTag($tagid, $nextOpenid = '')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/user/tag/get?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['tagid' => $tagid, 'next_openid' => $nextOpenid]);
    }

    /**
     * 获取公众号黑名单列表接口
     * @param string $beginOpenid 第一个拉取的openid，不填默认从头开始拉取
     * @return array 黑名单列表（total, count, data.openid, next_openid）
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getBlackList($beginOpenid = '')
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/getblacklist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['begin_openid' => $beginOpenid]);
    }

    /**
     * 批量拉黑用户接口
     * @param array $openids 用户openid列表（最多20个）
     * @return array 操作结果
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchBlackList(array $openids)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchblacklist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['openid_list' => $openids]);
    }

    /**
     * 批量取消拉黑用户接口
     * @param array $openids 用户openid列表（最多20个）
     * @return array 操作结果
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function batchUnblackList(array $openids)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/tags/members/batchunblacklist?access_token=ACCESS_TOKEN";
        return $this->callPostApi($url, ['openid_list' => $openids]);
    }
}