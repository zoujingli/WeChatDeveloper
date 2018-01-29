<?php

// +----------------------------------------------------------------------
// | WeChatDeveloper
// +----------------------------------------------------------------------
// | 版权所有 2014~2018 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: http://think.ctolog.com
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zoujingli/WeChatDeveloper
// +----------------------------------------------------------------------

namespace Wechat;

use Wechat\Contracts\Tools;
use Wechat\Contracts\Wechat;
use Wechat\Exceptions\InvalidResponseException;

/**
 * 微信前端支持
 * Class Script
 * @package Wechat
 */
class Script extends Wechat
{

    /**
     * 删除JSAPI授权TICKET
     * @param string $type TICKET类型(wx_card|jsapi)
     * @param string $appid 强制指定有效APPID
     * @return void
     */
    public function delTicket($type = 'jsapi', $appid = null)
    {
        is_null($appid) && $appid = $this->config->get('appid');
        $cache_name = "wechat_{$type}_ticket_{$appid}";
        Tools::delCache($cache_name);
    }

    /**
     * 获取JSAPI_TICKET接口
     * @param string $type TICKET类型(wx_card|jsapi)
     * @param string $appid 强制指定有效APPID
     * @return string
     * @throws Exceptions\InvalidResponseException
     * @throws Exceptions\LocalCacheException
     */
    public function getTicket($type = 'jsapi', $appid = null)
    {
        is_null($appid) && $appid = $this->config->get('appid');
        $cache_name = "wechat_{$type}_ticket_{$appid}";
        $ticket = Tools::getCache($cache_name);
        if (empty($ticket)) {
            $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=ACCESS_TOKEN&type={$type}";
            $this->registerApi($url, __FUNCTION__, func_get_args());
            $result = $this->httpGetForJson($url);
            if (empty($result['ticket'])) {
                throw new InvalidResponseException('Invalid Resoponse Ticket.', '0');
            }
            $ticket = $result['ticket'];
            Tools::setCache($cache_name, $ticket, 5000);
        }
        return $ticket;
    }

    /**
     * 获取JsApi使用签名
     * @param string $url 网页的URL
     * @param string $appid 用于多个appid时使用(可空)
     * @param string $ticket 强制指定ticket
     * @return array
     * @throws Exceptions\LocalCacheException
     * @throws InvalidResponseException
     */
    public function getJsSign($url, $appid = null, $ticket = null)
    {
        list($url,) = explode('#', $url);
        is_null($ticket) && $ticket = $this->getTicket('jsapi');
        is_null($appid) && $appid = $this->config->get('appid');
        $data = ["url" => $url, "timestamp" => '' . time(), "jsapi_ticket" => $ticket, "noncestr" => Tools::createNoncestr(16)];
        return [
            'debug'     => false,
            "appId"     => $appid,
            "nonceStr"  => $data['noncestr'],
            "timestamp" => $data['timestamp'],
            "signature" => Tools::getSignature($data, 'sha1'),
            'jsApiList' => [
                'onWXDeviceBluetoothStateChange', 'onWXDeviceStateChange',
                'openProductSpecificView', 'addCard', 'chooseCard', 'openCard',
                'translateVoice', 'getNetworkType', 'openLocation', 'getLocation',
                'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone',
                'chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'closeWindow', 'scanQRCode', 'chooseWXPay',
                'hideOptionMenu', 'showOptionMenu', 'hideMenuItems', 'showMenuItems', 'hideAllNonBaseMenuItem', 'showAllNonBaseMenuItem',
                'startScanWXDevice', 'stopScanWXDevice', 'onWXDeviceBindStateChange', 'onScanWXDeviceResult', 'onReceiveDataFromWXDevice',
                'startRecord', 'stopRecord', 'onVoiceRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice',
                'openWXDeviceLib', 'closeWXDeviceLib', 'getWXDeviceInfos', 'sendDataToWXDevice', 'disconnectWXDevice', 'getWXDeviceTicket', 'connectWXDevice',
            ],
        ];
    }
}