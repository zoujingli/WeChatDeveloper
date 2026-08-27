<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Common\Transport\HttpTransportInterface;

/**
 * 测试使用的同步记录型传输适配器。
 *
 * @internal
 */
final class RecordingTransport implements HttpTransportInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<int> */
    public array $timeouts = [];

    /** @param list<ResponseInterface|\Throwable> $responses */
    public function __construct(private array $responses = []) {}

    public function send(RequestInterface $request, int $timeoutMilliseconds = 20_000): ResponseInterface
    {
        $this->requests[] = $request;
        $this->timeouts[] = $timeoutMilliseconds;
        $next = array_shift($this->responses) ?? new Response(200, ['Content-Type' => 'application/json'], '{}');
        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }
}
