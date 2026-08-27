<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use We\Alipay\AliPayConfig;
use We\Alipay\AliRestConfig;
use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\InvalidCallException;
use We\Common\MultipartPart;
use We\Common\Request;
use We\Common\Runtime;
use We\Wechat\WeChatConfig;
use We\Wechat\WxPayConfig;
use We\WeChatClient;

/**
 * 请求、配置和请求体编码契约测试。
 *
 * @internal
 * @coversNothing
 */
final class RequestTest extends TestCase
{
    public function testRequestIsImmutableAndKeepsOrderedDuplicateQuery(): void
    {
        $original = Request::get('cgi-bin/example');
        $changed = $original
            ->query([['tag', 'a'], ['tag', 'b']])
            ->headers(['X-Trace-Id' => 'trace']);
        $transport = new RecordingTransport();
        $client = WeChatClient::mk(
            new WeChatConfig('wx_app', 'secret'),
            new Runtime(transport: $transport),
        );

        $client->call($original->anonymous());
        $client->call($changed->anonymous());

        self::assertNotSame($original, $changed);
        self::assertSame('', $transport->requests[0]->getUri()->getQuery());
        self::assertSame('tag=a&tag=b', $transport->requests[1]->getUri()->getQuery());
        self::assertSame('trace', $transport->requests[1]->getHeaderLine('X-Trace-Id'));
    }

    public function testTargetRejectsAbsoluteOrAmbiguousValues(): void
    {
        foreach (['https://example.com/a', '//example.com/a', 'path?a=1', 'path#fragment'] as $target) {
            try {
                Request::get($target);
                self::fail('预期无效目标被拒绝：' . $target);
            } catch (InvalidCallException) {
            }
        }

        self::assertInstanceOf(Request::class, Request::post('alipay.trade.query'));
        $transport = new RecordingTransport();
        $client = WeChatClient::mk(
            new WeChatConfig('wx_app', 'secret'),
            new Runtime(transport: $transport),
        );
        $client->call(Request::get('/v3/payments/1')->anonymous());
        self::assertSame('/v3/payments/1', $transport->requests[0]->getUri()->getPath());
    }

    public function testHeadersRejectInjection(): void
    {
        $this->expectException(InvalidCallException::class);
        Request::get('example')->headers(['X-Test' => "value\r\nAuthorization: leaked"]);
    }

    public function testHeadersAndDownloadTargetRejectInvalidRuntimeValues(): void
    {
        try {
            /** @phpstan-ignore-next-line 验证无类型配置数组也进入 SDK 异常。 */
            Request::get('example')->headers(['X-Test' => 123]);
            self::fail('预期无效请求头值被拒绝');
        } catch (InvalidCallException) {
        }

        $resource = fopen('php://memory', 'r');
        self::assertIsResource($resource);
        try {
            Request::get('example')->downloadTo(Utils::streamFor($resource));
            self::fail('预期不可写下载目标被拒绝');
        } catch (InvalidCallException) {
        }

        $path = tempnam(sys_get_temp_dir(), 'we-request-test-');
        self::assertIsString($path);
        $writeOnly = fopen($path, 'wb');
        self::assertIsResource($writeOnly);
        try {
            Request::post('example')->raw(Utils::streamFor($writeOnly));
            self::fail('预期不可读请求体流被拒绝');
        } catch (InvalidCallException) {
        } finally {
            if (is_resource($writeOnly)) {
                fclose($writeOnly);
            }
            @unlink($path);
        }
    }

    public function testBodyEncoderCoversEveryWireBody(): void
    {
        $stream = Utils::streamFor('PREFIX-STREAM');
        $stream->seek(7);
        $requests = [
            Request::post('example'),
            Request::post('example')->json(['ok' => true]),
            Request::post('example')->form([['tag', 'a'], ['tag', 'b']]),
            Request::post('example')->raw('RAW', 'text/plain'),
            Request::post('example')->raw($stream),
            Request::post('example')->multipart(
                new MultipartPart('metadata', '{"type":"image"}', mediaType: 'application/json'),
                new MultipartPart('file', Utils::streamFor('FILE'), 'demo.jpg', 'image/jpeg'),
            ),
        ];
        $transport = new RecordingTransport();
        $client = WeChatClient::mk(
            new WeChatConfig('wx_app', 'secret'),
            new Runtime(transport: $transport),
        );
        foreach ($requests as $request) {
            $client->call($request->anonymous());
        }

        self::assertSame('', (string)$transport->requests[0]->getBody());
        self::assertSame('{"ok":true}', (string)$transport->requests[1]->getBody());
        self::assertSame('tag=a&tag=b', (string)$transport->requests[2]->getBody());
        self::assertSame('RAW', (string)$transport->requests[3]->getBody());
        self::assertSame('STREAM', $transport->requests[4]->getBody()->getContents());
        self::assertSame('6', $transport->requests[4]->getHeaderLine('Content-Length'));
        self::assertStringContainsString('filename="demo.jpg"', (string)$transport->requests[5]->getBody());
    }

    public function testEndpointAndArrayConfigsFailClosed(): void
    {
        try {
            new Endpoint('http://example.com');
            self::fail('预期无效端点被拒绝');
        } catch (ConfigurationException) {
        }

        $payment = WxPayConfig::fromArray([
            'appid' => 'wx_app',
            'mch_id' => 'mch',
            'merchant_serial' => 'merchant-serial',
            'merchant_private_key' => TestKeys::privateKey(),
            'platform_serial' => 'platform-serial',
            'platform_public_key' => TestKeys::platformKeyPair()[1],
        ]);
        $gateway = AliPayConfig::fromArray([
            'appid' => 'ali_app',
            'private_key' => TestKeys::privateKeyBody(),
            'alipay_public_key' => TestKeys::publicKeyBody(),
        ]);
        $rest = AliRestConfig::fromArray([
            'appid' => 'ali_app',
            'private_key' => TestKeys::privateKeyBody(),
            'alipay_public_key' => TestKeys::publicKeyBody(),
        ]);

        self::assertSame('merchant-serial', $payment->merchantSigner->keyId());
        self::assertSame('ali_app', $gateway->appid);
        self::assertSame('ali_app', $rest->appid);
    }
}
