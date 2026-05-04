<?php

declare(strict_types=1);

namespace We\Platform\Alipay;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use We\Config\AlipayPlatformConfig;
use We\Exception\WechatException;

class PlatformClient
{
    protected ClientInterface $http;

    public function __construct(
        protected readonly AlipayPlatformConfig $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient(['timeout' => 20.0]);
    }

    /** @param array<string,mixed> $bizContent @param array<string,mixed> $extra @return array<string,mixed> */
    public function request(string $apiMethod, array $bizContent = [], array $extra = []): array
    {
        $params = [
            'app_id' => $this->config->appid,
            'method' => $apiMethod,
            'format' => $this->config->format,
            'charset' => $this->config->charset,
            'sign_type' => $this->config->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->config->version,
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
        foreach ($extra as $key => $value) {
            $params[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $params['sign'] = $this->sign($params);
        $response = $this->http->request('POST', $this->config->gateway, [
            'form_params' => $params,
            'headers' => ['Accept' => 'application/json'],
        ]);
        $payload = json_decode((string)$response->getBody(), true);
        if (!is_array($payload)) {
            throw new WechatException('支付宝网关响应格式无效');
        }
        $node = str_replace('.', '_', $apiMethod) . '_response';
        $data = is_array($payload[$node] ?? null) ? $payload[$node] : $payload;
        if (($data['code'] ?? '10000') !== '10000') {
            throw new WechatException((string)($data['sub_msg'] ?? $data['msg'] ?? '支付宝接口调用失败'));
        }

        return $data;
    }

    public function auth(string $redirectUri, string $scope = 'auth_user', string $state = ''): string
    {
        return 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?' . http_build_query([
            'app_id' => $this->config->appid,
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    /** @return array<string,mixed> */
    public function decrypt(string $encryptedData, string $sessionKey, string $iv): array
    {
        $plain = openssl_decrypt(
            base64_decode($encryptedData, true) ?: '',
            'AES-128-CBC',
            base64_decode($sessionKey, true) ?: '',
            OPENSSL_RAW_DATA,
            base64_decode($iv, true) ?: ''
        );
        if (!is_string($plain) || $plain === '') {
            throw new WechatException('支付宝数据解密失败');
        }
        $data = json_decode($plain, true);
        if (!is_array($data)) {
            throw new WechatException('支付宝解密结果无效');
        }

        return $data;
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $apiMethod = trim($uriOrPath);
        if ($apiMethod === 'auth') {
            return ['url' => $this->auth(
                (string)($params['redirect_uri'] ?? ''),
                (string)($params['scope'] ?? 'auth_user'),
                (string)($params['state'] ?? ''),
            )];
        }
        if ($apiMethod === 'decrypt') {
            return $this->decrypt(
                (string)($params['encrypted_data'] ?? ''),
                (string)($params['session_key'] ?? ''),
                (string)($params['iv'] ?? ''),
            );
        }

        return $this->request($apiMethod, $params, $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }

    /** @param array<string,mixed> $params */
    protected function sign(array $params): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || $value === null || $value === '') {
                continue;
            }
            $pairs[] = $key . '=' . $value;
        }
        $data = implode('&', $pairs);
        $privateKey = str_contains($this->config->privateKey, 'BEGIN') ? $this->config->privateKey : "-----BEGIN PRIVATE KEY-----\n" . chunk_split($this->config->privateKey, 64, "\n") . "-----END PRIVATE KEY-----";
        $resource = openssl_pkey_get_private($privateKey);
        if ($resource === false) {
            throw new WechatException('支付宝私钥无效');
        }
        $algo = strtoupper($this->config->signType) === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $ok = openssl_sign($data, $signature, $resource, $algo);
        if ($ok !== true) {
            throw new WechatException('支付宝签名失败');
        }

        return base64_encode($signature);
    }
}
