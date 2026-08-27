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
use We\Common\Transport\BodyEncoder;
use We\Wechat\WxPayConfig;

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
            ->headers(['X-Trace-Id' => 'trace'])
            ->rawMedia();

        self::assertNotSame($original, $changed);
        self::assertSame([], $original->query);
        self::assertSame([['tag', 'a'], ['tag', 'b']], $changed->query);
        self::assertTrue($changed->hasHeader('x-trace-id'));
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

        self::assertSame('alipay.trade.query', Request::post('alipay.trade.query')->target);
        self::assertSame('v3/payments/1', Request::get('/v3/payments/1')->target);
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
        $encoder = new BodyEncoder();
        $encoded = array_map($encoder->encode(...), $requests);

        self::assertSame('', (string)$encoded[0]->stream);
        self::assertSame('{"ok":true}', (string)$encoded[1]->stream);
        self::assertSame('tag=a&tag=b', (string)$encoded[2]->stream);
        self::assertSame('RAW', (string)$encoded[3]->stream);
        self::assertSame('STREAM', $encoded[4]->stream->getContents());
        self::assertSame(6, $encoded[4]->contentLength);
        self::assertStringContainsString('filename="demo.jpg"', (string)$encoded[5]->stream);
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
