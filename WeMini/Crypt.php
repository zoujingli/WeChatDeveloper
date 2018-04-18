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

namespace WeMini;

use WeChat\Contracts\BasicWeChat;

/**
 * 数据加密处理
 * Class Crypt
 * @package WeMini
 */
class Crypt extends BasicWeChat
{

    /**
     * 数据签名校验
     * @param string $iv
     * @param string $encryptedData
     * @param string $sessionKey
     * @return bool
     */
    public function decode($iv, $encryptedData, $sessionKey)
    {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'wxBizDataCrypt.php';
        $pc = new \WXBizDataCrypt($this->config->get('appid'), $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        if ($errCode == 0) {
            return $data;
        }
        return false;
    }
}