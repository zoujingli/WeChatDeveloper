<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Exception\StreamException;
use We\Common\Exception\TransportException;
use We\Common\Transport\HttpTransportInterface;
use We\Common\Transport\Spooler;

/**
 * 发送并校验微信生态内部 Token HTTP 请求。
 *
 * @internal
 */
final class TokenHttpClient
{
    private const MAX_RESPONSE_BYTES = 1_048_576;

    private const TIMEOUT_MILLISECONDS = 20_000;

    public function __construct(
        private readonly HttpTransportInterface $transport,
        private readonly Spooler $spooler,
    ) {}

    /** @return array<string,mixed> */
    public function get(string|UriInterface $uri, string $channel): array
    {
        return $this->send(new Request('GET', $uri, ['Accept' => 'application/json']), $channel);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function postJson(string|UriInterface $uri, array $payload, string $channel): array
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $exception) {
            throw new ProtocolException('微信 Token 请求 JSON 编码失败', 0, $exception, channel: $channel);
        }

        return $this->send(new Request('POST', $uri, [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], Utils::streamFor($json)), $channel);
    }

    /** @return array<string,mixed> */
    private function send(RequestInterface $request, string $channel): array
    {
        try {
            $response = $this->transport->send($request, self::TIMEOUT_MILLISECONDS);
        } catch (TransportException $exception) {
            throw new TransportException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $exception->context(),
                $channel,
                $exception->requestId(),
                $exception->platformCode(),
            );
        }
        $requestId = $this->requestId($response->getHeaders());
        try {
            $spool = $this->spooler->spool($response->getBody(), self::MAX_RESPONSE_BYTES);
        } catch (StreamException $exception) {
            throw new StreamException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $exception->context(),
                $channel,
                $requestId,
            );
        }

        try {
            $body = $spool->contents();
        } catch (StreamException $exception) {
            throw new StreamException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $exception->context(),
                $channel,
                $requestId,
            );
        } finally {
            $spool->stream->close();
        }
        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            if ($response->getStatusCode() >= 400) {
                throw new PlatformException(
                    '微信 Token 端点返回 HTTP 错误',
                    0,
                    $exception,
                    ['status' => $response->getStatusCode()],
                    $channel,
                    $requestId,
                    $response->getStatusCode(),
                );
            }
            throw new ProtocolException('微信 Token 响应不是有效 JSON', 0, $exception, channel: $channel, requestId: $requestId);
        }
        if (!is_array($data)) {
            if ($response->getStatusCode() >= 400) {
                throw new PlatformException(
                    '微信 Token 端点返回 HTTP 错误',
                    context: ['status' => $response->getStatusCode()],
                    channel: $channel,
                    requestId: $requestId,
                    platformCode: $response->getStatusCode(),
                );
            }
            throw new ProtocolException('微信 Token 响应结构无效', channel: $channel, requestId: $requestId);
        }
        if ((int)($data['errcode'] ?? 0) !== 0) {
            $code = (int)$data['errcode'];
            throw new PlatformException(
                (string)($data['errmsg'] ?? '微信 Token 获取失败'),
                context: [
                    'status' => $response->getStatusCode(),
                    'errcode' => $code,
                    'errmsg' => is_scalar($data['errmsg'] ?? null) ? (string)$data['errmsg'] : null,
                ],
                channel: $channel,
                requestId: $requestId,
                platformCode: $code,
            );
        }
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '微信 Token 端点返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $channel,
                requestId: $requestId,
                platformCode: $response->getStatusCode(),
            );
        }

        return $data;
    }

    /** @param array<string,list<string>> $headers */
    private function requestId(array $headers): ?string
    {
        $names = ['Request-Id', 'Wechatpay-Request-Id', 'Alipay-Trace-Id'];
        foreach ($headers as $name => $values) {
            foreach ($names as $candidate) {
                if (strcasecmp($name, $candidate) === 0 && $values !== []) {
                    return implode(', ', $values);
                }
            }
        }

        return null;
    }
}
