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

namespace Wechat\Contracts;

use Wechat\Exceptions\InvalidArgumentException;
use Wechat\Exceptions\InvalidResponseException;

/**
 * Class Wechat
 * @package Wechat\Contracts
 */
class Wechat
{

    /**
     * 当前微信配置
     * @var Config
     */
    public $config;

    /**
     * 访问AccessToken
     * @var string
     */
    public $access_token = '';

    /**
     * 当前请求方法参数
     * @var array
     */
    private $currentMethod = [];

    /**
     * 当前模式
     * @var bool
     */
    private $isTry = false;

    /**
     * Wechat constructor.
     * @param array $options
     */
    public function __construct(array $options)
    {
        if (empty($options['appid'])) {
            throw new InvalidArgumentException("Missing Config -- [appid]");
        }
        if (empty($options['appsecret'])) {
            throw new InvalidArgumentException("Missing Config -- [appsecret]");
        }
        $this->config = new Config($options);
    }

    /**
     * 获取访问accessToken
     * @return string
     * @throws \Wechat\Exceptions\InvalidResponseException
     * @throws \Wechat\Exceptions\LocalCacheException
     */
    public function getAccesstoken()
    {
        if (!empty($this->access_token)) {
            return $this->access_token;
        }
        $cacheKey = $this->config->get('appid') . '_accesstoken';
        $this->access_token = Tools::getCache($cacheKey);
        if (!empty($this->access_token)) {
            return $this->access_token;
        }
        list($appid, $secret) = [$this->config->get('appid'), $this->config->get('appsecret')];
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$appid}&secret={$secret}";
        $result = Tools::fromJson(Tools::get($url));
        if (!empty($result['access_token'])) {
            Tools::setCache($cacheKey, $result['access_token'], 6000);
        }
        return $result['access_token'];
    }

    /**
     * 清理删除accessToken
     * @return bool
     */
    public function delAccessToken()
    {
        $this->access_token = '';
        return Tools::delCache($this->config->get('appid') . '_accesstoken');
    }


    /**
     * 以GET获取接口数据并转为数组
     * @param string $url 接口地址
     * @return array
     */
    protected function httpGetForJson($url)
    {
        try {
            return Tools::fromJson(Tools::get($url));
        } catch (InvalidResponseException $e) {
            if (!$this->isTry && in_array($e->getCode(), ['40014', '40001', '41001', '42001'])) {
                $this->delAccessToken();
                $this->isTry = true;
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
        }
    }

    /**
     * 以POST获取接口数据并转为数组
     * @param string $url 接口地址
     * @param array $data 请求数据
     * @param bool $buildToJson
     * @return array
     */
    protected function httpPostForJson($url, array $data, $buildToJson = true)
    {
        try {
            return Tools::fromJson(Tools::post($url, $buildToJson ? Tools::toJson($data) : $data));
        } catch (InvalidResponseException $e) {
            if (!$this->isTry && in_array($e->getCode(), ['40014', '40001', '41001', '42001'])) {
                $this->delAccessToken();
                $this->isTry = true;
                return call_user_func_array([$this, $this->currentMethod['method']], $this->currentMethod['arguments']);
            }
        }
    }

    /**
     * 注册当前请求接口
     * @param string $url 接口地址
     * @param string $method 当前接口方法
     * @param array $arguments 请求参数
     * @return mixed
     * @throws \Wechat\Exceptions\InvalidResponseException
     * @throws \Wechat\Exceptions\LocalCacheException
     */
    protected function registerApi(&$url, $method, $arguments = [])
    {
        $this->currentMethod = ['method' => $method, 'arguments' => $arguments];
        if (empty($this->access_token)) {
            $this->access_token = $this->getAccesstoken();
        }
        return $url = str_replace('ACCESS_TOKEN', $this->access_token, $url);
    }

}