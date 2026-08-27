<?php

declare(strict_types=1);

namespace We\Common\Transport;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Common\Exception\TransportException;

/** 使用 Guzzle 发送同步 HTTP 请求的生产适配器。 */
final class GuzzleTransport implements HttpTransportInterface
{
    public function __construct(private readonly ClientInterface $client = new Client()) {}

    public function send(RequestInterface $request, int $timeoutMilliseconds = 20_000): ResponseInterface
    {
        try {
            return $this->client->send($request, [
                'allow_redirects' => false,
                'http_errors' => false,
                'timeout' => $timeoutMilliseconds / 1000,
            ]);
        } catch (GuzzleException $exception) {
            throw new TransportException(
                '平台 HTTP 传输失败',
                (int)$exception->getCode(),
                null,
                [
                    'cause' => $exception::class,
                    'method' => $request->getMethod(),
                    'host' => $request->getUri()->getHost(),
                    'path' => $request->getUri()->getPath(),
                ],
            );
        }
    }
}
