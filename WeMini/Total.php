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
 * 小程序数据统计
 * @package WeMini
 */
class Total extends BasicWeChat
{
    /**
     * 概况趋势（日）
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期，需同一天
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidDailySummarytrend($beginDate, $endDate)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailysummarytrend?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $beginDate, 'end_date' => $endDate], true);
    }

    /**
     * 访问趋势（日）
     * @param string $beginDate 开始日期
     * @param string $endDate 结束日期，需同一天
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidDailyVisittrend($beginDate, $endDate)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailyvisittrend?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $beginDate, 'end_date' => $endDate], true);
    }

    /**
     * 访问趋势（周）
     * @param string $begin_date 周一
     * @param string $end_date 周日
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidWeeklyVisittrend($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidweeklyvisittrend?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 访问趋势（月）
     * @param string $begin_date 月首日
     * @param string $end_date 月末日
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidMonthlyVisittrend($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidmonthlyvisittrend?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 访问分布
     * @param string $begin_date 开始日期
     * @param string $end_date 结束日期，需同一天
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidVisitdistribution($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidvisitdistribution?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 留存（日）
     * @param string $begin_date 开始日期
     * @param string $end_date 结束日期，需同一天
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidDailyRetaininfo($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappiddailyretaininfo?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 留存（周）
     * @param string $begin_date 周一
     * @param string $end_date 周日
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidWeeklyRetaininfo($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidweeklyretaininfo?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 留存（月）
     * @param string $begin_date 月首日
     * @param string $end_date 月末日
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidMonthlyRetaininfo($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidmonthlyretaininfo?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 访问页面数据
     * @param string $begin_date 开始日期
     * @param string $end_date 结束日期，需同一天
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidVisitPage($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappidvisitpage?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }

    /**
     * 用户画像
     * @param string $begin_date 开始日期
     * @param string $end_date 结束日期，间隔为0/6/29
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function getWeanalysisAppidUserportrait($begin_date, $end_date)
    {
        $url = 'https://api.weixin.qq.com/datacube/getweanalysisappiduserportrait?access_token=ACCESS_TOKEN';
        return $this->callPostApi($url, ['begin_date' => $begin_date, 'end_date' => $end_date], true);
    }
}