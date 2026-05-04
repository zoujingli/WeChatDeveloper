<?php

declare(strict_types=1);

namespace We\Platform\Wechat;

use GuzzleHttp\ClientInterface;
use We\Config\WechatPaymentConfig;
use We\Exception\SignatureException;
use We\Exception\WechatException;
use We\Support\JsonClient;
use We\Support\PayCrypto;
use We\Support\Signature;

final class PaymentClient
{
    private JsonClient $http;

    public function __construct(
        private readonly WechatPaymentConfig $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = new JsonClient($http ?? new \GuzzleHttp\Client(['base_uri' => 'https://api.mch.weixin.qq.com/', 'timeout' => 20.0]));
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function request(string $method, string $uri, array $payload = [], array $query = []): array
    {
        $uri = '/' . ltrim($uri, '/');
        $body = $payload === [] ? '' : (json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string)time();
        $authorization = $this->authorization($method, $uri . ($query === [] ? '' : '?' . http_build_query($query)), $timestamp, $nonce, $body);

        return $this->http->request($method, ltrim($uri, '/'), $query, [
            'body' => $body,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $authorization,
                'Wechatpay-Serial' => $this->config->merchantSerial,
            ],
        ]);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function refund(array $payload): array
    {
        return $this->request('POST', 'v3/refund/domestic/refunds', $payload);
    }

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>|string $body 原始 JSON 字符串优先；传数组仅用于兼容旧调用，验签会退化为本地重编码。
     * @return array<string,mixed>
     */
    public function decryptNotification(array $headers, array|string $body): array
    {
        // 微信支付 APIv3 签名串要求使用 HTTP 原始 body；不能先 json_decode 再重新编码，否则字段顺序或转义差异会导致验签失败。
        $rawBody = is_string($body) ? $body : (json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
        $this->assertNotificationSignature($headers, $rawBody);
        $payload = is_string($body) ? json_decode($body, true) : $body;
        if (!is_array($payload)) {
            throw new WechatException('微信支付回调 JSON 无效');
        }
        /** @var array{ciphertext:string,nonce:string,associated_data?:string} $resource */
        $resource = $payload['resource'] ?? [];

        return PayCrypto::decryptResource($this->config->apiV3Key, $resource);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $uri = ltrim($uriOrPath, '/');
        $method = strtoupper($httpMethod === '' ? 'POST' : $httpMethod);
        if ($uri === 'refund') {
            return $this->refund($params);
        }
        if ($uri === 'decrypt_notification') {
            $body = $options['raw_body'] ?? $options['body'] ?? $params;
            return $this->decryptNotification(
                is_array($options['headers'] ?? null) ? $options['headers'] : [],
                is_string($body) || is_array($body) ? $body : $params,
            );
        }

        return $this->request(
            $method,
            $uri,
            $method === 'GET' ? (is_array($options['payload'] ?? null) ? $options['payload'] : []) : $params,
            $method === 'GET' ? $params : (is_array($options['query'] ?? null) ? $options['query'] : []),
        );
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

    private function authorization(string $method, string $uri, string $timestamp, string $nonce, string $body): string
    {
        $message = strtoupper($method) . "\n{$uri}\n{$timestamp}\n{$nonce}\n{$body}\n";
        $signature = Signature::payV3Sign($this->config->merchantPrivateKey, $message);
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $fields = [
            'mchid' => $this->config->mchId,
            'nonce_str' => $nonce,
            'timestamp' => $timestamp,
            'serial_no' => $this->config->merchantSerial,
            'signature' => $signature,
        ];

        return $schema . ' ' . implode(',', array_map(static fn (string $key, string $value): string => $key . '="' . $value . '"', array_keys($fields), $fields));
    }

    private function assertNotificationSignature(array $headers, string $body): void
    {
        $timestamp = $this->headerValue($headers, 'Wechatpay-Timestamp');
        $nonce = $this->headerValue($headers, 'Wechatpay-Nonce');
        $signature = $this->headerValue($headers, 'Wechatpay-Signature');
        $serial = $this->headerValue($headers, 'Wechatpay-Serial');
        if ($this->config->platformSerial !== '' && !hash_equals($this->config->platformSerial, $serial)) {
            throw new SignatureException('微信支付平台序列号不匹配');
        }
        $message = "{$timestamp}\n{$nonce}\n{$body}\n";
        $publicKey = $this->config->platformPublicKey !== '' ? $this->config->platformPublicKey : $this->config->platformCertificate;
        if ($publicKey === '') {
            throw new SignatureException('微信支付平台公钥或证书不能为空');
        }
        if (!Signature::verifyPayV3($publicKey, $message, $signature)) {
            throw new SignatureException('微信支付回调验签失败');
        }
    }

    /**
     * @param array<string,mixed> $headers
     */
    private function headerValue(array $headers, string $name): string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string)$key, $name) === 0) {
                return is_array($value) ? implode(',', array_map('strval', $value)) : (string)$value;
            }
        }

        return '';
    }
}
