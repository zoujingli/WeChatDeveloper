<?php

declare(strict_types=1);

namespace We\Platform\Alipay;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use We\Config\AlipayPlatformConfig;
use We\Exception\AlipayApiException;
use We\Exception\AlipayException;
use We\Exception\AlipaySignatureException;
use We\Exception\TransportException;
use We\Support\CredentialValidator;

/**
 * 支付宝开放平台客户端。
 *
 * 负责组装支付宝开放平台网关公共参数、生成 RSA/RSA2 签名、验证同步响应和异步通知签名。
 */
class PlatformClient
{
    protected ClientInterface $http;

    /**
     * 创建支付宝开放平台客户端并初始化网关 HTTP 客户端。
     */
    public function __construct(
        protected readonly AlipayPlatformConfig $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient(['timeout' => 20.0]);
    }

    /**
     * 调用支付宝开放平台网关接口。
     *
     * @param array<string,mixed> $bizContent
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public function request(string $apiMethod, array $bizContent = [], array $extra = []): array
    {
        $params = $this->buildGatewayParams($apiMethod, $bizContent, $extra);
        try {
            $response = $this->http->request('POST', $this->config->gateway, [
                'form_params' => $params,
                'headers' => ['Accept' => 'application/json'],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $e) {
            throw new TransportException(
                '支付宝网关请求失败: ' . $e->getMessage(),
                (int)$e->getCode(),
                $e,
                ['platform' => 'alipay', 'method' => $apiMethod],
            );
        }
        $body = (string)$response->getBody();
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new AlipayApiException('支付宝网关响应格式无效', 0, null, ['body' => $body]);
        }
        $node = str_replace('.', '_', $apiMethod) . '_response';
        if (is_array($payload[$node] ?? null)) {
            $responseNode = $node;
        } elseif (is_array($payload['error_response'] ?? null)) {
            $responseNode = 'error_response';
        } else {
            throw new AlipayApiException('支付宝响应缺少节点: ' . $node, 0, null, $payload);
        }
        $this->assertResponseSignature($body, $responseNode, (string)($payload['sign'] ?? ''));
        $data = $payload[$responseNode];
        if (!array_key_exists('code', $data) || !is_scalar($data['code'])) {
            throw new AlipayApiException('支付宝响应缺少有效 code', 0, null, $data);
        }
        if ((string)$data['code'] !== '10000') {
            throw new AlipayApiException(
                (string)($data['sub_msg'] ?? $data['msg'] ?? '支付宝接口调用失败'),
                (int)$data['code'],
                null,
                $data,
            );
        }

        return $data;
    }

    /**
     * 验证支付宝异步通知签名；业务处理通知前应先完成验签。
     *
     * @param array<string,mixed> $params 支付宝通知完整参数，包含 sign/sign_type
     */
    public function verifyNotify(array $params): bool
    {
        return $this->verify($params);
    }

    /**
     * 验证支付宝参数签名；按开放平台规则排除 sign/sign_type 后排序拼接待验签内容。
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
            throw new AlipayException('支付宝公钥不能为空');
        }

        return $this->verifySignature($this->buildSignContent($params, true), $sign);
    }

    /**
     * 生成支付宝开放平台网页授权地址。
     */
    public function auth(string $redirectUri, string $scope = 'auth_user', string $state = ''): string
    {
        return 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?' . http_build_query([
            'app_id' => $this->config->appid,
            'scope' => $scope,
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    /**
     * 解密支付宝小程序等场景返回的 AES 加密数据。
     *
     * @return array<string,mixed>
     */
    public function decrypt(string $encryptedData, string $sessionKey, string $iv): array
    {
        $ciphertext = base64_decode($encryptedData, true);
        $key = base64_decode($sessionKey, true);
        $ivValue = base64_decode($iv, true);
        if ($ciphertext === false || $key === false || $ivValue === false) {
            throw new AlipayException('支付宝数据解密参数 Base64 无效');
        }
        $plain = openssl_decrypt(
            $ciphertext,
            'AES-128-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $ivValue
        );
        if (!is_string($plain) || $plain === '') {
            throw new AlipayException('支付宝数据解密失败');
        }
        $data = json_decode($plain, true);
        if (!is_array($data)) {
            throw new AlipayException('支付宝解密结果无效');
        }

        return $data;
    }

    /**
     * 通用网关调用入口；特殊操作名用于授权地址生成和数据解密。
     *
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
     * 按 POST 语义调用支付宝开放平台接口。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * 按 GET 语义调用支付宝开放平台接口。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }

    /**
     * 使用应用私钥对支付宝网关请求参数生成签名。
     *
     * @param array<string,mixed> $params
     */
    protected function sign(array $params): string
    {
        $data = $this->buildSignContent($params);
        $privateKey = $this->normalizePrivateKey($this->config->privateKey);
        $resource = openssl_pkey_get_private($privateKey);
        if ($resource === false) {
            throw new AlipayException('支付宝私钥无效');
        }
        $algo = strtoupper($this->config->signType) === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $ok = openssl_sign($data, $signature, $resource, $algo);
        if ($ok !== true) {
            throw new AlipayException('支付宝签名失败');
        }

        return base64_encode($signature);
    }

    /**
     * 组装支付宝开放平台网关公共参数并附加签名。
     *
     * @param array<string,mixed> $bizContent
     * @param array<string,mixed> $extra
     * @return array<string,string>
     */
    protected function buildGatewayParams(string $apiMethod, array $bizContent = [], array $extra = []): array
    {
        $params = [
            'app_id' => $this->config->appid,
            'method' => $apiMethod,
            'format' => $this->config->format,
            'charset' => $this->config->charset,
            'sign_type' => $this->config->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->config->version,
            'biz_content' => $this->jsonString($bizContent, '{}'),
        ];
        foreach ($extra as $key => $value) {
            $params[(string)$key] = $this->gatewayValue($value);
        }
        $params['sign'] = $this->sign($params);

        return $params;
    }

    /**
     * 使用支付宝公钥校验网关同步响应签名。
     */
    private function assertResponseSignature(string $body, string $node, string $sign): void
    {
        if ($sign === '') {
            throw new AlipaySignatureException('支付宝响应缺少签名');
        }

        // 支付宝同步响应的验签原文是响应节点的原始 JSON 片段，不能使用 json_decode 后重新编码的数组。
        if (!$this->verifySignature($this->extractJsonValue($body, $node), $sign)) {
            throw new AlipaySignatureException('支付宝响应验签失败');
        }
    }

    /**
     * 按支付宝开放平台规则排序并拼接待签名字符串。
     *
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
            $pairs[] = $key . '=' . $this->gatewayValue($value);
        }

        return implode('&', $pairs);
    }

    /**
     * 将网关扩展参数规范化为支付宝表单字符串。
     */
    private function gatewayValue(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : $this->jsonString($value, '');
    }

    /**
     * 将支付宝网关数组参数编码为 JSON 字符串。
     */
    private function jsonString(mixed $value, string $fallback): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json : $fallback;
    }

    /**
     * 使用支付宝公钥验证 RSA/RSA2 签名。
     */
    private function verifySignature(string $source, string $signature): bool
    {
        $publicKey = $this->normalizePublicKey($this->config->alipayPublicKey);
        $resource = openssl_pkey_get_public($publicKey);
        if ($resource === false) {
            throw new AlipayException('支付宝公钥无效');
        }
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }
        $algo = strtoupper($this->config->signType) === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;

        return openssl_verify($source, $decoded, $resource, $algo) === 1;
    }

    /**
     * 将应用私钥内容规范化为 PEM 格式。
     */
    private function normalizePrivateKey(string $privateKey): string
    {
        return CredentialValidator::normalizePrivateKey($privateKey, true);
    }

    /**
     * 将支付宝公钥内容规范化为 PEM 格式。
     */
    private function normalizePublicKey(string $publicKey): string
    {
        return CredentialValidator::normalizePublicKey($publicKey, true);
    }

    /**
     * 从支付宝网关原始 JSON 响应中提取用于验签的响应节点。
     */
    private function extractJsonValue(string $json, string $key): string
    {
        if (preg_match('/"' . preg_quote($key, '/') . '"\s*:\s*/', $json, $match, PREG_OFFSET_CAPTURE) !== 1) {
            throw new AlipayApiException('支付宝响应缺少签名节点: ' . $key);
        }
        $start = (int)$match[0][1] + strlen((string)$match[0][0]);
        $length = strlen($json);
        while ($start < $length && ctype_space($json[$start])) {
            ++$start;
        }

        $first = $json[$start] ?? '';
        if ($first !== '{' && $first !== '[') {
            throw new AlipayApiException('支付宝响应签名节点格式无效');
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

        throw new AlipayApiException('支付宝响应签名节点不完整');
    }
}
