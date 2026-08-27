<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use We\AliPayClient;
use We\AliRestClient;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\StreamException;
use We\Common\Exception\TransportException;
use We\Common\Request;
use We\Common\Runtime;
use We\Wechat\Common\Internal\CacheKey;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\WeChatConfig;
use We\Wechat\WxAppConfig;
use We\Wechat\WxOpenConfig;
use We\WeChatClient;
use We\WxAppClient;
use We\WxOpenClient;
use We\WxPayClient;

/**
 * 六个通道的公开调用契约测试。
 *
 * @internal
 * @coversNothing
 */
final class ClientTest extends TestCase
{
    public function testEveryChannelOwnsItsTypedFactory(): void
    {
        $transport = new RecordingTransport();
        $runtime = new Runtime(transport: $transport);
        $channels = [
            WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), $runtime),
            WxAppClient::mk(new WxAppConfig('wx_app', 'secret'), $runtime),
            WxOpenClient::mk(new WxOpenConfig('component_app', 'secret'), $runtime),
            WxPayClient::mk(ProtocolFixtures::wxPayConfig(), $runtime),
            AliPayClient::mk(ProtocolFixtures::aliPayConfig(), $runtime),
            AliRestClient::mk(ProtocolFixtures::aliRestConfig(), $runtime),
        ];

        self::assertSame([
            'wechat.platform',
            'wechat.wxapp',
            'wechat.service',
            'wechat.payment',
            'alipay.gateway',
            'alipay.rest',
        ], array_map(static fn ($channel): string => $channel->channel(), $channels));
    }

    public function testWeChatCallUsesCachedTokenAndParsesJson(): void
    {
        $cache = new MemoryCache();
        $cache->set(CacheKey::compose(
            Runtime::DEFAULT_CACHE_KEY_PREFIX,
            WeChatClient::NAME,
            TokenCacheKey::weChatAccessToken('wx_app'),
        ), 'cached-token', 3600);
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Content-Type' => 'application/json'], '{"users":[1,2]}'),
        ]);
        $client = WeChatClient::mk(
            new WeChatConfig('wx_app', 'secret'),
            new Runtime(cache: $cache, transport: $transport),
        );

        $data = $client->call(Request::get('cgi-bin/user/get')->query(['next_openid' => 'NEXT']))->json();

        self::assertSame(['users' => [1, 2]], $data);
        self::assertSame('next_openid=NEXT&access_token=cached-token', $transport->requests[0]->getUri()->getQuery());
    }

    public function testAnonymousAndRawResponsesNeedNoExtraResultTypes(): void
    {
        $transport = new RecordingTransport([
            new PsrResponse(204),
            new PsrResponse(200, ['Content-Type' => 'text/plain'], 'RAW'),
        ]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));

        $empty = $client->call(Request::get('sns/example')->anonymous());
        $raw = $client->call(Request::get('raw')->anonymous());

        self::assertSame(204, $empty->status());
        self::assertSame('', $transport->requests[0]->getUri()->getQuery());
        self::assertSame(200, $raw->status());
        self::assertSame('RAW', $raw->raw());
        $raw->body()->close();
    }

    public function testExplicitBinaryContentTypeDoesNotSniffLeadingJsonByte(): void
    {
        $contents = '{BINARY-DATA';
        $client = WeChatClient::mk(
            new WeChatConfig('wx_app', 'secret'),
            new Runtime(transport: new RecordingTransport([
                new PsrResponse(200, ['Content-Type' => 'application/octet-stream'], $contents),
            ])),
        );

        $response = $client->call(Request::get('binary')->anonymous());

        self::assertSame($contents, $response->raw());
        $response->body()->close();
    }

    public function testJsonErrorDoesNotPolluteDownloadDestination(): void
    {
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Content-Type' => 'application/json'], '{"errcode":40001,"errmsg":"bad token"}'),
        ]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));
        $destination = Utils::streamFor('');

        try {
            $client->call(Request::get('wxa/getwxacodeunlimit')->anonymous()->downloadTo($destination));
            self::fail('预期平台错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame(40001, $exception->platformCode());
        }
        self::assertSame('', (string)$destination);
    }

    public function testReservedHeaderAndSpoolLimitFailBeforeExposure(): void
    {
        $transport = new RecordingTransport([new PsrResponse(200, [], 'TOO-LARGE')]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));

        try {
            $client->call(Request::get('example')->anonymous()->headers(['Authorization' => 'caller-value']));
            self::fail('预期协议保留请求头被拒绝');
        } catch (InvalidCallException) {
            self::assertSame([], $transport->requests);
        }
        try {
            $client->call(Request::get('example')->anonymous()->query(['access_token' => 'caller-token']));
            self::fail('预期协议保留查询参数被拒绝');
        } catch (InvalidCallException) {
            self::assertSame([], $transport->requests);
        }

        $this->expectException(StreamException::class);
        $client->call(Request::get('raw')->anonymous()->maxResponseBytes(4));
    }

    public function testWxOpenAuthorizerUsesReferencedToken(): void
    {
        $cache = new MemoryCache();
        $cache->set(CacheKey::compose(
            Runtime::DEFAULT_CACHE_KEY_PREFIX,
            WxOpenClient::NAME,
            TokenCacheKey::wechatOpenAuthorizerAccessToken('component_app', 'authorizer_app'),
        ), 'authorizer-token', 3600);
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
        ]);
        $client = WxOpenClient::mk(
            new WxOpenConfig('component_app', 'component_secret'),
            new Runtime(cache: $cache, transport: $transport),
        );

        $client->call(Request::get('cgi-bin/user/get')->asWechatAuthorizer('authorizer_app'));

        self::assertStringContainsString('access_token=authorizer-token', $transport->requests[0]->getUri()->getQuery());
    }

    public function testDerivedResourcePolicyIsAppliedAtSecondCall(): void
    {
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Content-Type' => 'application/json'], '{"url":"https://127.0.0.1/private"}'),
        ]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));
        $source = $client->call(Request::get('resource')->anonymous());

        $this->expectException(InvalidCallException::class);
        $client->call(Request::get($source->resource('url'))->downloadTo(Utils::streamFor('')));
    }

    public function testErrorsKeepDiagnosticContextWithoutPayload(): void
    {
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Request-Id' => 'request-123'], '{"errcode":40003,"errmsg":"invalid","openid":"sensitive-user"}'),
        ]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));

        try {
            $client->call(Request::get('example')->anonymous());
            self::fail('预期平台错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame(40003, $exception->platformCode());
            self::assertSame('request-123', $exception->requestId());
            self::assertArrayNotHasKey('openid', $exception->context());
        }
    }

    public function testTransportFailureIsEnrichedWithChannelAndTimeout(): void
    {
        $transport = new RecordingTransport([
            new TransportException('network down', context: ['host' => 'api.weixin.qq.com']),
        ]);
        $client = WeChatClient::mk(new WeChatConfig('wx_app', 'secret'), new Runtime(transport: $transport));

        try {
            $client->call(Request::get('example')->anonymous()->timeout(1234));
            self::fail('预期传输错误被转换');
        } catch (TransportException $exception) {
            self::assertSame(WeChatClient::NAME, $exception->channel());
            self::assertSame('api.weixin.qq.com', $exception->context()['host']);
            self::assertSame([1234], $transport->timeouts);
        }
    }
}
