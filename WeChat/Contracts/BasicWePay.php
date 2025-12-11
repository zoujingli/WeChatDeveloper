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

namespace WeChat\Contracts;

use WeChat\Exceptions\InvalidArgumentException;
use WeChat\Exceptions\InvalidResponseException;

/**
 * 微信支付基础类
 * @package WeChat\Contracts
 */
class BasicWePay
{
    /**
     * 静态缓存
     * @var static
     */
    protected static $cache;
    /**
     * 商户配置
     * @var DataArray
     */
    protected $config;
    /**
     * 当前请求数据
     * @var DataArray
     */
    protected $params;

    /**
     * 构造函数
     * @param array $options 必填：appid、mch_id、mch_key，可选：sub_appid、sub_mch_id、cache_path
     */
    public function __construct(array $options)
    {
        if (empty($options['appid'])) {
            throw new InvalidArgumentException("Missing Config -- [appid]");
        }
        if (empty($options['mch_id'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_id]");
        }
        if (empty($options['mch_key'])) {
            throw new InvalidArgumentException("Missing Config -- [mch_key]");
        }
        if (!empty($options['cache_path'])) {
            Tools::$cache_path = $options['cache_path'];
        }
        $this->config = new DataArray($options);
        // 商户基础参数
        $this->params = new DataArray([
            'appid'     => $this->config->get('appid'),
            'mch_id'    => $this->config->get('mch_id'),
            'nonce_str' => Tools::createNoncestr(),
        ]);
        // 商户参数支持
        if ($this->config->get('sub_appid')) {
            $this->params->set('sub_appid', $this->config->get('sub_appid'));
        }
        if ($this->config->get('sub_mch_id')) {
            $this->params->set('sub_mch_id', $this->config->get('sub_mch_id'));
        }
    }

    /**
     * 静态创建对象
     * @param array $config 商户配置
     * @return static
     */
    public static function instance(array $config)
    {
        $key = md5(get_called_class() . serialize($config));
        if (isset(self::$cache[$key])) return self::$cache[$key];
        return self::$cache[$key] = new static($config);
    }

    /**
     * 获取微信支付异步通知并验签
     * @param string|array $xml 可选，默认读取原始输入
     * @return array 验签通过的通知数据
     * @throws \WeChat\Exceptions\InvalidResponseException
     */
    public function getNotify($xml = '')
    {
        $data = is_array($xml) ? $xml : Tools::xml2arr(empty($xml) ? Tools::getRawInput() : $xml);
        if (isset($data['sign']) && $this->getPaySign($data) === $data['sign']) {
            return $data;
        }
        throw new InvalidResponseException('Invalid Notify.', '0');
    }

    /**
     * 生成支付签名
     * @param array $data 待签名数据
     * @param string $signType MD5|HMAC-SHA256
     * @param string $buff 签名前缀（内部使用）
     * @return string 大写签名
     */
    public function getPaySign(array $data, $signType = 'MD5', $buff = '')
    {
        ksort($data);
        if (isset($data['sign'])) unset($data['sign']);
        foreach ($data as $k => $v) {
            if ('' === $v || null === $v) continue;
            $buff .= "{$k}={$v}&";
        }
        $buff .= ("key=" . $this->config->get('mch_key'));
        if (strtoupper($signType) === 'MD5') {
            return strtoupper(md5($buff));
        }
        return strtoupper(hash_hmac('SHA256', $buff, $this->config->get('mch_key')));
    }

    /**
     * 获取微信支付通知成功回复 XML
     * @return string
     */
    public function getNotifySuccessReply()
    {
        return Tools::arr2xml(['return_code' => 'SUCCESS', 'return_msg' => 'OK']);
    }

    /**
     * 转换短链接（tools/shorturl）
     * @param string $longUrl 需转换的URL，签名用原串，传输需URLencode
     * @return array
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function shortUrl($longUrl)
    {
        $url = 'https://api.mch.weixin.qq.com/tools/shorturl';
        return $this->callPostApi($url, ['long_url' => $longUrl]);
    }

    /**
     * 基础 POST 调用（自动签名，可选双向证书）
     * @param string $url 请求地址
     * @param array $data 接口参数
     * @param bool $isCert 是否需要双向证书（退款等场景）
     * @param string $signType MD5|HMAC-SHA256
     * @param bool $needSignType 是否追加 sign_type 字段
     * @param bool $needNonceStr 是否自动附加 nonce_str
     * @return array XML 解析结果
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    protected function callPostApi($url, array $data, $isCert = false, $signType = 'HMAC-SHA256', $needSignType = true, $needNonceStr = true)
    {
        $option = [];
        if ($isCert) {
            $option['ssl_p12'] = $this->config->get('ssl_p12');
            $option['ssl_cer'] = $this->config->get('ssl_cer');
            $option['ssl_key'] = $this->config->get('ssl_key');
            if (is_string($option['ssl_p12']) && file_exists($option['ssl_p12'])) {
                $content = file_get_contents($option['ssl_p12']);
                if (openssl_pkcs12_read($content, $certs, $this->config->get('mch_id'))) {
                    $option['ssl_key'] = Tools::pushFile(md5($certs['pkey']) . '.pem', $certs['pkey']);
                    $option['ssl_cer'] = Tools::pushFile(md5($certs['cert']) . '.pem', $certs['cert']);
                } else throw new InvalidArgumentException("P12 certificate does not match MCH_ID --- ssl_p12");
            }
            if (empty($option['ssl_cer']) || !file_exists($option['ssl_cer'])) {
                throw new InvalidArgumentException("Missing Config -- ssl_cer", '0');
            }
            if (empty($option['ssl_key']) || !file_exists($option['ssl_key'])) {
                throw new InvalidArgumentException("Missing Config -- ssl_key", '0');
            }
        }
        $params = $this->params->merge($data);
        if (!$needNonceStr) unset($params['nonce_str']);
        if ($needSignType) $params['sign_type'] = strtoupper($signType);
        $params['sign'] = $this->getPaySign($params, $signType);
        $result = Tools::xml2arr(Tools::post($url, Tools::arr2xml($params), $option));
        if ($result['return_code'] !== 'SUCCESS') {
            throw new InvalidResponseException($result['return_msg'], '0');
        }
        return $result;
    }

    /**
     * 数组转 XML 输出
     * @param array $data 待转换数据
     * @param bool $isReturn true 返回字符串，false 直接输出
     * @return string|void
     */
    public function toXml(array $data, $isReturn = false)
    {
        $xml = Tools::arr2xml($data);
        if ($isReturn) return $xml;
        echo $xml;
    }

    /**
     * 通用接口（V2）调用
     * @param string $url 完整 URL
     * @param array|string $data 请求参数，数组自动补全商户参数并签名；字符串原样发送
     * @param string $method GET|POST|PUT|DELETE|PATCH，默认 POST（GET/HEAD/OPTIONS 不带签名）
     * @param bool $isCert 是否启用双向证书
     * @param string $signType MD5|HMAC-SHA256，默认 HMAC-SHA256
     * @return array XML 解析结果
     * @throws \WeChat\Exceptions\InvalidArgumentException
     * @throws \WeChat\Exceptions\InvalidResponseException
     * @throws \WeChat\Exceptions\LocalCacheException
     */
    public function callApi($url, $data = [], $method = 'POST', $isCert = false, $signType = 'HMAC-SHA256')
    {
        $method = strtoupper($method);

        // 处理数据格式
        $requestData = is_array($data) ? $data : (is_string($data) ? $data : []);

        // GET/HEAD/OPTIONS请求（无请求体，不签名）
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            if (!empty($requestData) && is_array($requestData)) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($requestData);
            }
            $response = Tools::get($url);
            $result = Tools::xml2arr($response);
            if (isset($result['return_code']) && $result['return_code'] !== 'SUCCESS') {
                throw new InvalidResponseException($result['return_msg'], '0');
            }
            return $result;
        }

        // POST/PUT/PATCH/DELETE请求（有请求体）
        $option = [];
        if ($isCert) {
            $option['ssl_p12'] = $this->config->get('ssl_p12');
            $option['ssl_cer'] = $this->config->get('ssl_cer');
            $option['ssl_key'] = $this->config->get('ssl_key');
            if (is_string($option['ssl_p12']) && file_exists($option['ssl_p12'])) {
                $content = file_get_contents($option['ssl_p12']);
                if (openssl_pkcs12_read($content, $certs, $this->config->get('mch_id'))) {
                    $option['ssl_key'] = Tools::pushFile(md5($certs['pkey']) . '.pem', $certs['pkey']);
                    $option['ssl_cer'] = Tools::pushFile(md5($certs['cert']) . '.pem', $certs['cert']);
                } else {
                    throw new InvalidArgumentException("P12 certificate does not match MCH_ID --- ssl_p12");
                }
            }
            if (empty($option['ssl_cer']) || !file_exists($option['ssl_cer'])) {
                throw new InvalidArgumentException("Missing Config -- ssl_cer", '0');
            }
            if (empty($option['ssl_key']) || !file_exists($option['ssl_key'])) {
                throw new InvalidArgumentException("Missing Config -- ssl_key", '0');
            }
        }

        // 合并参数并处理签名（默认自动签名）
        if (is_array($requestData)) {
            $params = $this->params->merge($requestData);
            $params['sign_type'] = strtoupper($signType);
            $params['sign'] = $this->getPaySign($params, $signType);
            $option['data'] = Tools::arr2xml($params);
        } else {
            $option['data'] = $requestData;
        }

        // 使用doRequest支持PUT/DELETE/PATCH
        $response = Tools::doRequest($method, $url, $option);

        $result = Tools::xml2arr($response);
        if (isset($result['return_code']) && $result['return_code'] !== 'SUCCESS') {
            throw new InvalidResponseException($result['return_msg'], '0');
        }
        return $result;
    }
}
