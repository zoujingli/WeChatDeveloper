<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request as PsrRequest;
use PHPUnit\Framework\TestCase;
use We\Common\Exception\TransportException;
use We\Common\Transport\GuzzleTransport;

/**
 * HTTP 传输诊断信息安全测试。
 *
 * @internal
 * @coversNothing
 */
final class TransportSecurityTest extends TestCase
{
    public function testGuzzleFailureDoesNotExposeSignedQueryOrToken(): void
    {
        $request = new PsrRequest('GET', 'https://api.example.com/path?access_token=SECRET');
        $handler = new MockHandler([
            new ConnectException('连接失败：https://api.example.com/path?access_token=SECRET', $request),
        ]);
        $transport = new GuzzleTransport(new GuzzleClient(['handler' => HandlerStack::create($handler)]));

        try {
            $transport->send($request, 1500);
            self::fail('预期传输失败被转换');
        } catch (TransportException $exception) {
            self::assertSame('平台 HTTP 传输失败', $exception->getMessage());
            self::assertStringNotContainsString('SECRET', json_encode($exception->context(), JSON_THROW_ON_ERROR));
            self::assertSame('api.example.com', $exception->context()['host']);
            self::assertSame('/path', $exception->context()['path']);
        }
    }
}
