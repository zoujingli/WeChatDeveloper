<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Config\AlipayPlatformConfig;
use We\Exception\AlipayApiException;
use We\Exception\AlipaySignatureException;
use We\Exception\TransportException;
use We\Platform\Alipay\PlatformClient as AlipayPlatformClient;

/**
 * 支付宝开放平台网关调用与验签测试用例。
 * @internal
 */
#[CoversClass(AlipayPlatformClient::class)]
final class AlipayPlatformClientTest extends TestCase
{
    /**
     * 测试配置支付宝公钥时会校验同步响应签名。
     */
    public function testRequestVerifiesSignedResponseWhenPublicKeyConfigured(): void
    {
        [$alipayPrivateKey, $alipayPublicKey] = TestKeys::platformKeyPair();
        $responseNode = '{"code":"10000","msg":"Success","trade_no":"TRADE202605040001"}';
        $body = '{"alipay_trade_query_response":' . $responseNode . ',"sign":"' . self::sign($responseNode, $alipayPrivateKey) . '"}';
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient($body),
        );

        $data = $client->request('alipay.trade.query', ['out_trade_no' => 'P202605040001']);

        self::assertSame('TRADE202605040001', $data['trade_no']);
    }

    /**
     * 测试支付宝异步通知验签。
     */
    public function testVerifyNotify(): void
    {
        [$alipayPrivateKey, $alipayPublicKey] = TestKeys::platformKeyPair();
        $params = [
            'notify_time' => '2026-05-04 12:00:00',
            'app_id' => 'ali_app',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => 'P202605040001',
            'sign_type' => 'RSA2',
        ];
        $params['sign'] = self::sign('app_id=ali_app&notify_time=2026-05-04 12:00:00&out_trade_no=P202605040001&trade_status=TRADE_SUCCESS', $alipayPrivateKey);
        $client = new AlipayPlatformClient(new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey));

        self::assertTrue($client->verifyNotify($params));
    }

    public function testRequestRejectsSignedResponseWithoutBusinessCode(): void
    {
        [$alipayPrivateKey, $alipayPublicKey] = TestKeys::platformKeyPair();
        $responseNode = '{"msg":"Success","trade_no":"TRADE_WITHOUT_CODE"}';
        $body = '{"alipay_trade_query_response":' . $responseNode . ',"sign":"' . self::sign($responseNode, $alipayPrivateKey) . '"}';
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient($body),
        );

        $this->expectException(AlipayApiException::class);
        $this->expectExceptionMessage('code');

        $client->request('alipay.trade.query');
    }

    public function testRequestExposesSignedPlatformErrorContext(): void
    {
        [$alipayPrivateKey, $alipayPublicKey] = TestKeys::platformKeyPair();
        $responseNode = '{"code":"40004","msg":"Business Failed","sub_code":"ACQ.TRADE_NOT_EXIST","sub_msg":"交易不存在"}';
        $body = '{"error_response":' . $responseNode . ',"sign":"' . self::sign($responseNode, $alipayPrivateKey) . '"}';
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient($body),
        );

        try {
            $client->request('alipay.trade.query');
            self::fail('Expected a signed Alipay platform error.');
        } catch (AlipayApiException $exception) {
            self::assertSame(40004, $exception->getCode());
            self::assertSame('交易不存在', $exception->getMessage());
            self::assertSame('ACQ.TRADE_NOT_EXIST', $exception->context()['sub_code']);
        }
    }

    public function testRequestRejectsInvalidPlatformSignatureWithDistinctType(): void
    {
        [, $alipayPublicKey] = TestKeys::platformKeyPair();
        $responseNode = '{"code":"10000","msg":"Success"}';
        $body = '{"alipay_trade_query_response":' . $responseNode . ',"sign":"'
            . base64_encode('invalid-signature') . '"}';
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient($body),
        );

        $this->expectException(AlipaySignatureException::class);

        $client->request('alipay.trade.query');
    }

    public function testRequestRejectsMissingResponseNode(): void
    {
        [, $alipayPublicKey] = TestKeys::platformKeyPair();
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient('{}'),
        );

        $this->expectException(AlipayApiException::class);
        $this->expectExceptionMessage('节点');

        $client->request('alipay.trade.query');
    }

    public function testRequestRejectsNonJsonResponse(): void
    {
        [, $alipayPublicKey] = TestKeys::platformKeyPair();
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            new AlipayFakeHttpClient('not-json'),
        );

        $this->expectException(AlipayApiException::class);
        $this->expectExceptionMessage('格式');

        $client->request('alipay.trade.query');
    }

    public function testRequestExposesTransportFailureType(): void
    {
        [, $alipayPublicKey] = TestKeys::platformKeyPair();
        $failure = new ConnectException(
            'connection failed',
            new Request('POST', 'https://openapi.alipay.com/gateway.do'),
        );
        $http = new Client([
            'handler' => HandlerStack::create(new MockHandler([$failure])),
        ]);
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            $http,
        );

        $this->expectException(TransportException::class);

        $client->request('alipay.trade.query');
    }

    public function testSignedHttpErrorRemainsAPlatformApiFailure(): void
    {
        [$alipayPrivateKey, $alipayPublicKey] = TestKeys::platformKeyPair();
        $responseNode = '{"code":"40004","msg":"Business Failed","sub_msg":"交易不存在"}';
        $body = '{"error_response":' . $responseNode . ',"sign":"'
            . self::sign($responseNode, $alipayPrivateKey) . '"}';
        $http = new Client([
            'handler' => HandlerStack::create(new MockHandler([
                new Response(400, ['Content-Type' => 'application/json'], $body),
            ])),
        ]);
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), $alipayPublicKey),
            $http,
        );

        try {
            $client->request('alipay.trade.query');
            self::fail('Expected a signed Alipay platform error.');
        } catch (AlipayApiException $exception) {
            self::assertSame(40004, $exception->getCode());
            self::assertSame('交易不存在', $exception->getMessage());
        }
    }

    /**
     * 使用测试私钥生成签名。
     */
    private static function sign(string $source, string $privateKey): string
    {
        $ok = openssl_sign($source, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        self::assertTrue($ok);

        return base64_encode($signature);
    }
}

/**
 * 返回固定网关响应的支付宝测试 HTTP 客户端。
 */
final class AlipayFakeHttpClient implements ClientInterface
{
    /**
     * 创建固定响应测试 HTTP 客户端。
     */
    public function __construct(private readonly string $body) {}

    /**
     * 实现测试 HTTP 客户端同步发送接口。
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->response();
    }

    /**
     * 实现测试 HTTP 客户端异步发送接口。
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('sendAsync is not used in this test'));
    }

    /**
     * 实现测试 HTTP 客户端请求接口或记录请求。
     * @param mixed $uri
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        return $this->response();
    }

    /**
     * 实现测试 HTTP 客户端异步请求接口。
     * @param mixed $uri
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('requestAsync is not used in this test'));
    }

    /**
     * 返回测试 HTTP 客户端配置。
     */
    public function getConfig(?string $option = null): mixed
    {
        return null;
    }

    /**
     * 构造测试 HTTP 响应对象。
     */
    private function response(): ResponseInterface
    {
        return new Response(200, [], $this->body);
    }
}
