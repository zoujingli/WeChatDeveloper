<?php

declare(strict_types=1);

/**
 * JSON HTTP 客户端封装。
 */

namespace We\Support;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use We\Exception\ApiException;

/**
 * JSON HTTP 客户端封装。
 *
 * 统一发送 JSON 风格平台接口请求，只允许相对路径，并将平台错误响应转换为 {@see ApiException}。
 */
final class JsonClient
{
    /**
     * 创建 JSON HTTP 客户端封装。
     */
    public function __construct(
        private ClientInterface $http = new Client(['timeout' => 20.0]),
    ) {}

    /**
     * 发送 HTTP 请求并将 JSON 响应解析为数组。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = []): array
    {
        $response = $this->raw($method, $uri, $query, $options);
        $body = (string)$response->getBody();
        $data = $body === '' ? [] : json_decode($body, true);
        if (!is_array($data)) {
            throw new ApiException('微信接口响应不是有效 JSON', (int)$response->getStatusCode(), null, ['body' => $body]);
        }
        $errcode = (int)($data['errcode'] ?? 0);
        if ($errcode !== 0) {
            throw new ApiException((string)($data['errmsg'] ?? '微信接口请求失败'), $errcode, null, $data);
        }

        return $data;
    }

    /**
     * 发送 HTTP 请求并返回原始 PSR-7 响应，不执行 JSON 解析。
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     */
    public function raw(string $method, string $uri, array $query = [], array $options = []): ResponseInterface
    {
        $options['query'] = array_merge($query, is_array($options['query'] ?? null) ? $options['query'] : []);

        return $this->send($method, $uri, $options);
    }

    /**
     * 发送底层 Guzzle 请求，并禁止绝对 URL。
     *
     * @param array<string,mixed> $options
     */
    public function send(string $method, string $uri, array $options = []): ResponseInterface
    {
        $this->assertRelativeUri($uri);
        try {
            return $this->http->request($method, $uri, $options);
        } catch (GuzzleException $exception) {
            throw new ApiException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }

    /**
     * 校验接口 path 必须为相对路径，避免调用方传入绝对 URL。
     */
    private function assertRelativeUri(string $uri): void
    {
        $uri = trim($uri);
        // SDK 的微信类客户端都绑定了官方 base_uri，禁止传入绝对 URL，避免网关代调用场景被放大成 SSRF。
        if (str_starts_with($uri, '//') || preg_match('#^[a-z][a-z0-9+.-]*:#i', $uri) === 1) {
            throw new ApiException('接口路径必须是相对路径');
        }
    }
}
