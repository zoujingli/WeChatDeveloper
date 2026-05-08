<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Platform\Wechat;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use We\Config\WechatPaymentConfig;
use We\Exception\ApiException;
use We\Exception\SignatureException;
use We\Exception\WechatException;
use We\Support\JsonClient;
use We\Support\PaymentCrypto;
use We\Support\Signature;

/**
 * 微信支付 APIv3 客户端。
 *
 * 负责生成微信支付 APIv3 请求签名，发送商户平台 API 请求，并完成支付通知验签与 resource 解密。
 */
final class PaymentClient
{
    private JsonClient $http;

    /**
     * 创建微信支付 APIv3 客户端并初始化商户平台 API HTTP 客户端。
     */
    public function __construct(
        private readonly WechatPaymentConfig $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = new JsonClient($http ?? new Client(['base_uri' => 'https://api.mch.weixin.qq.com/', 'timeout' => 20.0]));
    }

    /**
     * 发起微信支付 APIv3 请求并附加商户请求签名。
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $payload = [], array $query = [], array $options = []): array
    {
        $response = $this->raw($method, $uri, $payload, $query, $options);
        $statusCode = (int)$response->getStatusCode();
        $body = (string)$response->getBody();
        $data = $body === '' ? [] : json_decode($body, true);
        if (!is_array($data)) {
            throw new ApiException('微信支付接口响应不是有效 JSON', $statusCode, null, ['body' => $body]);
        }
        if ($statusCode >= 400) {
            throw new ApiException((string)($data['message'] ?? $data['code'] ?? '微信支付接口请求失败'), $statusCode, null, $data);
        }

        return $data;
    }

    /**
     * 发起微信支付 APIv3 请求并返回原始响应。
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function raw(string $method, string $uri, array $payload = [], array $query = [], array $options = []): ResponseInterface
    {
        $uri = '/' . ltrim($uri, '/');
        $query = $this->mergeQuery($query, $options);
        $body = $this->resolveRequestBody($payload, $options);
        $nonce = bin2hex(random_bytes(16));
        $timestamp = (string)time();
        $authorization = $this->authorization($method, $uri . $this->queryString($query), $timestamp, $nonce, $body);
        $options['body'] = $body;
        $options['headers'] = $this->headers($options, $authorization);
        unset($options['query']);

        return $this->http->raw($method, ltrim($uri, '/'), $query, $options);
    }

    /**
     * 下载微信支付 APIv3 资源并返回原始响应。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function download(string $uri, array $query = [], array $options = []): ResponseInterface
    {
        return $this->raw('GET', $uri, [], $query, $options);
    }

    /**
     * 通用 API 调用入口；官方接口使用 API path + 参数数组，特殊路径仅用于支付通知验签与解密。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $uri = ltrim($uriOrPath, '/');
        $method = strtoupper($httpMethod === '' ? 'POST' : $httpMethod);
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
            $this->paymentCallPayload($method, $params, $options),
            $this->paymentCallQuery($method, $params, $options),
            $this->paymentCallOptions($options),
        );
    }

    /**
     * 按 POST 方法调用微信支付 APIv3。
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
     * 按 GET 方法调用微信支付 APIv3。
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
     * 校验微信支付通知签名并解密通知 resource。
     *
     * @param array<string,string> $headers
     * @param array<string,mixed>|string $body 原始 JSON 字符串优先；传数组仅用于兼容旧调用，验签会退化为本地重编码
     * @return array<string,mixed>
     */
    private function decryptNotification(array $headers, array|string $body): array
    {
        // 微信支付 APIv3 签名串要求使用 HTTP 原始 body；不能先 json_decode 再重新编码，否则字段顺序或转义差异会导致验签失败。
        $rawBody = is_string($body) ? $body : $this->encodeJsonBody($body);
        $this->assertNotificationSignature($headers, $rawBody);
        $payload = is_string($body) ? json_decode($body, true) : $body;
        if (!is_array($payload)) {
            throw new WechatException('微信支付回调 JSON 无效');
        }
        $resource = $payload['resource'] ?? null;
        if (!is_array($resource)) {
            throw new WechatException('微信支付回调 resource 无效');
        }

        return PaymentCrypto::decryptResource($this->config->apiV3Key, $resource);
    }

    /**
     * 生成微信支付 APIv3 `WECHATPAY2-SHA256-RSA2048` Authorization 请求头。
     */
    private function authorization(string $method, string $uri, string $timestamp, string $nonce, string $body): string
    {
        $message = strtoupper($method) . "\n{$uri}\n{$timestamp}\n{$nonce}\n{$body}\n";
        $signature = Signature::paymentV3Sign($this->config->merchantPrivateKey, $message);
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

    /**
     * 校验微信支付通知头中的平台证书/公钥序列号与 RSA-SHA256 签名。
     *
     * @param array<string,mixed> $headers
     */
    private function assertNotificationSignature(array $headers, string $body): void
    {
        $timestamp = $this->headerValue($headers, 'Wechatpay-Timestamp');
        $nonce = $this->headerValue($headers, 'Wechatpay-Nonce');
        $signature = $this->headerValue($headers, 'Wechatpay-Signature');
        $serial = $this->headerValue($headers, 'Wechatpay-Serial');
        if ($timestamp === '' || $nonce === '' || $signature === '' || $serial === '') {
            throw new SignatureException('微信支付回调验签请求头不完整');
        }
        if ($this->config->platformSerial !== '' && !hash_equals($this->config->platformSerial, $serial)) {
            throw new SignatureException('微信支付平台序列号不匹配');
        }
        $message = "{$timestamp}\n{$nonce}\n{$body}\n";
        $publicKey = $this->config->platformPublicKey !== '' ? $this->config->platformPublicKey : $this->config->platformCertificate;
        if ($publicKey === '') {
            throw new SignatureException('微信支付平台公钥或证书不能为空');
        }
        if (!Signature::verifyPaymentV3($publicKey, $message, $signature)) {
            throw new SignatureException('微信支付回调验签失败');
        }
    }

    /**
     * 按名称读取微信支付通知请求头，兼容大小写差异。
     *
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

    /**
     * 合并显式 query 与 Guzzle options 中的 query，确保签名串与实际请求一致。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function mergeQuery(array $query, array $options): array
    {
        return array_merge($query, is_array($options['query'] ?? null) ? $options['query'] : []);
    }

    /**
     * 解析微信支付 APIv3 请求体，确保参与签名的 body 与实际发送的 body 完全一致。
     *
     * @param array<string,mixed> $payload
     * @param array<string,mixed> $options
     */
    private function resolveRequestBody(array $payload, array &$options): string
    {
        if (array_key_exists('body', $options)) {
            unset($options['json']);

            return (string)$options['body'];
        }
        if (array_key_exists('json', $options)) {
            $body = $this->encodeJsonBody($options['json']);
            unset($options['json']);

            return $body;
        }

        return $payload === [] ? '' : $this->encodeJsonBody($payload);
    }

    /**
     * 编码微信支付 APIv3 JSON 请求体。
     */
    private function encodeJsonBody(mixed $payload): string
    {
        try {
            return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $e) {
            throw new WechatException('微信支付请求 JSON 编码失败', 0, $e);
        }
    }

    /**
     * 构造通用调用的请求 payload；GET 默认无 body，可通过 options.payload 显式传入。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function paymentCallPayload(string $method, array $params, array $options): array
    {
        return $method === 'GET' ? (is_array($options['payload'] ?? null) ? $options['payload'] : []) : $params;
    }

    /**
     * 构造通用调用的 query，GET 使用 params，其他方法使用 options.query。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function paymentCallQuery(string $method, array $params, array $options): array
    {
        $query = $method === 'GET' ? $params : [];
        if (is_array($options['query'] ?? null)) {
            $query = array_merge($query, $options['query']);
        }

        return $query;
    }

    /**
     * 移除 SDK 内部控制项，其余 Guzzle options 继续透传到底层 HTTP 客户端。
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function paymentCallOptions(array $options): array
    {
        unset($options['payload'], $options['query'], $options['raw_body']);

        return $options;
    }

    /**
     * 构造签名所需的 query string。
     *
     * @param array<string,mixed> $query
     */
    private function queryString(array $query): string
    {
        return $query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * 合并默认微信支付 APIv3 请求头。
     *
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function headers(array $options, string $authorization): array
    {
        $headers = is_array($options['headers'] ?? null) ? $options['headers'] : [];
        $headers['Accept'] = $headers['Accept'] ?? 'application/json';
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json';
        $headers['Authorization'] = $authorization;

        return $headers;
    }
}
