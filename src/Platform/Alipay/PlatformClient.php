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
        $body = (string)$response->getBody();
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new WechatException('支付宝网关响应格式无效');
        }
        $node = str_replace('.', '_', $apiMethod) . '_response';
        $responseNode = is_array($payload[$node] ?? null) ? $node : (is_array($payload['error_response'] ?? null) ? 'error_response' : $node);
        if ($this->config->alipayPublicKey !== '') {
            $this->assertResponseSignature($body, $responseNode, (string)($payload['sign'] ?? ''));
        }
        $data = is_array($payload[$responseNode] ?? null) ? $payload[$responseNode] : $payload;
        if (($data['code'] ?? '10000') !== '10000') {
            throw new WechatException((string)($data['sub_msg'] ?? $data['msg'] ?? '支付宝接口调用失败'));
        }

        return $data;
    }

    /**
     * 验证支付宝异步通知签名；业务处理回调前应先调用该方法。
     *
     * @param array<string,mixed> $params 支付宝通知完整参数，包含 sign/sign_type。
     */
    public function verifyNotify(array $params): bool
    {
        return $this->verify($params);
    }

    /**
     * 验证支付宝参数签名；通知验签会排除 sign 与 sign_type，其他参数按字典序拼接。
     *
     * @param array<string,mixed> $params
     */
    public function verify(array $params): bool
    {
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') {
            return false;
        }
        if ($this->config->alipayPublicKey === '') {
            throw new WechatException('支付宝公钥不能为空');
        }

        return $this->verifySignature($this->buildSignContent($params, true), $sign);
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
        $data = $this->buildSignContent($params);
        $privateKey = $this->normalizePrivateKey($this->config->privateKey);
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

    private function assertResponseSignature(string $body, string $node, string $sign): void
    {
        if ($sign === '') {
            throw new WechatException('支付宝响应缺少签名');
        }

        // 支付宝同步响应的验签原文是响应节点的原始 JSON 片段，不能使用 json_decode 后重新编码的数组。
        if (!$this->verifySignature($this->extractJsonValue($body, $node), $sign)) {
            throw new WechatException('支付宝响应验签失败');
        }
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildSignContent(array $params, bool $skipSignType = false): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($key === 'sign' || ($skipSignType && $key === 'sign_type') || $value === null || $value === '') {
                continue;
            }
            $pairs[] = $key . '=' . (is_scalar($value) ? (string)$value : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: ''));
        }

        return implode('&', $pairs);
    }

    private function verifySignature(string $source, string $signature): bool
    {
        $publicKey = $this->normalizePublicKey($this->config->alipayPublicKey);
        $resource = openssl_pkey_get_public($publicKey);
        if ($resource === false) {
            throw new WechatException('支付宝公钥无效');
        }
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }
        $algo = strtoupper($this->config->signType) === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($source, $decoded, $resource, $algo) === 1;
    }

    private function normalizePrivateKey(string $privateKey): string
    {
        return str_contains($privateKey, 'BEGIN') ? $privateKey : "-----BEGIN PRIVATE KEY-----\n" . chunk_split($privateKey, 64, "\n") . "-----END PRIVATE KEY-----";
    }

    private function normalizePublicKey(string $publicKey): string
    {
        return str_contains($publicKey, 'BEGIN') ? $publicKey : "-----BEGIN PUBLIC KEY-----\n" . chunk_split($publicKey, 64, "\n") . "-----END PUBLIC KEY-----";
    }

    private function extractJsonValue(string $json, string $key): string
    {
        if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*/', $json, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw new WechatException('支付宝响应缺少签名节点: ' . $key);
        }
        $start = (int)$match[0][1] + strlen((string)$match[0][0]);
        $length = strlen($json);
        while ($start < $length && ctype_space($json[$start])) {
            ++$start;
        }

        $first = $json[$start] ?? '';
        if ($first !== '{' && $first !== '[') {
            throw new WechatException('支付宝响应签名节点格式无效');
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        for ($i = $start; $i < $length; ++$i) {
            $char = $json[$i];
            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }
            if ($char === '"') {
                $inString = true;
                continue;
            }
            if ($char === '{' || $char === '[') {
                ++$depth;
                continue;
            }
            if ($char === '}' || $char === ']') {
                --$depth;
                if ($depth === 0) {
                    return substr($json, $start, $i - $start + 1);
                }
            }
        }

        throw new WechatException('支付宝响应签名节点不完整');
    }
}
