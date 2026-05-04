<?php

declare(strict_types=1);

namespace We\Support;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use We\Exception\ApiException;

final class JsonClient
{
    public function __construct(
        private ClientInterface $http = new Client(['timeout' => 20.0]),
    ) {}

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function request(string $method, string $uri, array $query = [], array $options = []): array
    {
        $options['query'] = array_merge($query, $options['query'] ?? []);
        $response = $this->send($method, $uri, $options);
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
     * @param array<string,mixed> $options
     */
    public function send(string $method, string $uri, array $options = []): ResponseInterface
    {
        try {
            return $this->http->request($method, $uri, $options);
        } catch (GuzzleException $exception) {
            throw new ApiException($exception->getMessage(), (int)$exception->getCode(), $exception);
        }
    }
}
