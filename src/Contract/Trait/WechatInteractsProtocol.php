<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Contract\Trait;

use Psr\Http\Message\ResponseInterface;
use We\Exception\ApiException;

/**
 * 微信协议层客户端通用能力。
 *
 * 复用 client_credential access_token 缓存、原始响应、下载、上传和 JSON 请求选项构造逻辑。
 */
trait WechatInteractsProtocol
{
    /**
     * 获取并缓存 client_credential access_token。
     */
    private function clientCredentialAccessToken(string $logicalKey, string $appid, string $appSecret, bool $refresh): string
    {
        $key = $this->cacheKey($logicalKey);
        if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
            return $token;
        }

        return $this->cache->lock('lock:' . $key, 30, function () use ($key, $appid, $appSecret, $refresh): string {
            if (!$refresh && is_string($token = $this->cache->get($key, '')) && $token !== '') {
                return $token;
            }
            $data = $this->jsonWechatRequest('GET', 'cgi-bin/token', [
                'grant_type' => 'client_credential',
                'appid' => $appid,
                'secret' => $appSecret,
            ]);
            $token = $this->wechatTokenValue($data, 'access_token', '微信 access_token');
            $this->cache->set($key, $token, $this->wechatTokenTtl($data, '微信 access_token'));

            return $token;
        });
    }

    /**
     * 从微信 Token 响应中提取非空字符串 Token。
     *
     * @param array<string,mixed> $data
     */
    private function wechatTokenValue(array $data, string $field, string $label): string
    {
        $token = $data[$field] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new ApiException($label . ' 响应缺少 ' . $field, 0, null, $data);
        }

        return $token;
    }

    /**
     * 从微信 Token 响应中计算缓存 TTL。
     *
     * @param array<string,mixed> $data
     */
    private function wechatTokenTtl(array $data, string $label): int
    {
        $expiresIn = $data['expires_in'] ?? 7200;
        if (!is_numeric($expiresIn) || (int)$expiresIn <= 0) {
            throw new ApiException($label . ' 响应 expires_in 无效', 0, null, $data);
        }

        return max(1, (int)$expiresIn - 300);
    }

    /**
     * 按需在 query 中附加 access_token。
     *
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    private function withAccessToken(array $query, bool $withToken): array
    {
        if ($withToken) {
            /**
             * @phpstan-ignore-next-line 宿主为公众平台或小程序客户端时提供 accessToken()。
             */
            $query['access_token'] = $query['access_token'] ?? $this->accessToken();
        }

        return $query;
    }

    /**
     * 请求微信 JSON API 并解析响应数组。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function jsonWechatRequest(string $method, string $uri, array $query = [], array $options = []): array
    {
        return $this->http->request($method, ltrim($uri, '/'), $query, $options);
    }

    /**
     * 请求微信 API 并返回原始响应。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    private function rawWechatRequest(string $method, string $uri, array $query = [], array $options = []): ResponseInterface
    {
        return $this->http->raw($method, ltrim($uri, '/'), $query, $options);
    }

    /**
     * 下载微信二进制资源并返回原始响应。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    private function downloadWechatResource(string $uri, array $query = [], array $options = []): ResponseInterface
    {
        return $this->rawWechatRequest('GET', $uri, $query, $options);
    }

    /**
     * 使用 multipart/form-data 上传文件或媒体资源并解析 JSON 响应。
     *
     * @param array<int,array<string,mixed>> $multipart
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function uploadWechatResource(string $uri, array $multipart, array $query = [], array $options = []): array
    {
        $options['multipart'] = $multipart;

        return $this->jsonWechatRequest('POST', $uri, $query, $options);
    }

    /**
     * 处理微信消息安全模式伪路径。
     *
     * 使用该伪路径的宿主客户端需要提供 messageCrypto() 工厂。
     *
     * @param array<string,mixed> $params
     * @return null|array<string,mixed>
     */
    private function handleWechatMessageCryptoCall(string $uri, array $params): ?array
    {
        if ($uri === 'decrypt_message') {
            /**
             * @phpstan-ignore-next-line 只有提供 messageCrypto() 的宿主会调用消息加解密伪路径。
             */
            return $this->messageCrypto()->decryptMessage(
                (string)($params['body'] ?? ''),
                (string)($params['msg_signature'] ?? ''),
                (string)($params['timestamp'] ?? ''),
                (string)($params['nonce'] ?? ''),
            );
        }
        if ($uri === 'encrypt_message') {
            /**
             * @phpstan-ignore-next-line 只有提供 messageCrypto() 的宿主会调用消息加解密伪路径。
             */
            $xml = $this->messageCrypto()->encryptMessage(
                (string)($params['body'] ?? ''),
                (string)($params['timestamp'] ?? time()),
                (string)($params['nonce'] ?? ''),
            );

            return [
                'xml' => $xml,
            ];
        }

        return null;
    }

    /**
     * 按官方 path 与参数调用微信 JSON API。
     *
     * $tokenAware 为 true 时通过宿主 request() 的 withToken 参数控制 access_token 注入。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @param array<int,string> $internalKeys
     * @return array<string,mixed>
     */
    private function callWechatJsonApi(
        string $uriOrPath,
        array $params,
        string $httpMethod,
        array $options,
        bool $tokenAware,
        array $internalKeys = [],
    ): array {
        $method = $this->normalizeWechatHttpMethod($httpMethod);
        $uri = ltrim($uriOrPath, '/');
        $query = $this->wechatCallQuery($method, $params, $options);
        $requestOptions = $this->buildWechatJsonOptions($method, $params, $options, $internalKeys);
        if ($tokenAware) {
            /** @phpstan-ignore-next-line tokenAware 仅由支持 withToken 参数的公众平台和小程序客户端启用。 */
            return $this->request($method, $uri, $query, $requestOptions, (bool)($options['with_token'] ?? true));
        }

        return $this->request($method, $uri, $query, $requestOptions);
    }

    /**
     * 构造 Guzzle 请求选项；非 GET 且未显式传 body 时默认使用 JSON 请求体。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @param array<int,string> $internalKeys
     * @return array<string,mixed>
     */
    private function buildWechatJsonOptions(string $method, array $params, array $options, array $internalKeys = []): array
    {
        foreach ($internalKeys as $key) {
            unset($options[$key]);
        }
        if ($method === 'GET' || isset($options['json']) || isset($options['body']) || isset($options['form_params']) || isset($options['multipart'])) {
            return $options;
        }
        $options['json'] = $params;

        return $options;
    }

    /**
     * 规范化 HTTP 方法；空字符串按 POST 处理。
     */
    private function normalizeWechatHttpMethod(string $httpMethod): string
    {
        return strtoupper($httpMethod === '' ? 'POST' : $httpMethod);
    }

    /**
     * 根据 HTTP 方法从调用参数中提取 query。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function wechatCallQuery(string $method, array $params, array $options): array
    {
        return $method === 'GET' ? $params : (is_array($options['query'] ?? null) ? $options['query'] : []);
    }
}
