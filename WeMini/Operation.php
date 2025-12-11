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
 * 小程序运维中心
 * @package WeMini
 */
class Operation extends BasicWeChat
{

    /**
     * 实时日志查询
     * @param array $data 查询参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function realtimelogSearch($data)
    {
        $url = 'https://api.weixin.qq.com/wxaapi/userlog/userlog_search?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, $data, true);
    }

    /**
     * 获取反馈媒体文件
     * @param array $data query 参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getFeedbackmedia($data)
    {
        $query = http_build_query($data);
        $url = 'https://api.weixin.qq.com/cgi-bin/media/getfeedbackmedia?' . $query . '&access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }


    /**
     * 获取用户反馈列表
     * @param array $data query 参数
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getFeedback($data)
    {
        $query = http_build_query($data);
        $url = 'https://api.weixin.qq.com/wxaapi/userlog/userlog_search?' . $query . '&access_token=ACCESS_TOKEN';
        return $this->callGetApi($url);
    }
}