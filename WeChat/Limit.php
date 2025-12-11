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
 * 接口调用频次限制
 * @package WeChat
 */
class Limit extends BasicWeChat
{

    /**
     * 清空公众号 API 调用次数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function clearQuota()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/clear_quota?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['appid' => $this->config->get('appid')]);
    }

    /**
     * 网络检测
     * @param string $action ping|dns|all
     * @param string $operator DEFAULT|CT|CU|CM
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function ping($action = 'all', $operator = 'DEFAULT')
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/callback/check?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['action' => $action, 'check_operator' => $operator]);
    }

    /**
     * 获取微信服务器 IP
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getCallbackIp()
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/getcallbackip?access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }
}