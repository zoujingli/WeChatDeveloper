<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Config\AlipayPlatformConfig;
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
        [$privateKey, $publicKey] = self::keyPair();
        $responseNode = '{"code":"10000","msg":"Success","trade_no":"TRADE202605040001"}';
        $body = '{"alipay_trade_query_response":' . $responseNode . ',"sign":"' . self::sign($responseNode, $privateKey) . '"}';
        $client = new AlipayPlatformClient(
            new AlipayPlatformConfig('ali_app', $privateKey, $publicKey),
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
        [$privateKey, $publicKey] = self::keyPair();
        $params = [
            'notify_time' => '2026-05-04 12:00:00',
            'app_id' => 'ali_app',
            'trade_status' => 'TRADE_SUCCESS',
            'out_trade_no' => 'P202605040001',
            'sign_type' => 'RSA2',
        ];
        $params['sign'] = self::sign('app_id=ali_app&notify_time=2026-05-04 12:00:00&out_trade_no=P202605040001&trade_status=TRADE_SUCCESS', $privateKey);
        $client = new AlipayPlatformClient(new AlipayPlatformConfig('ali_app', $privateKey, $publicKey));

        self::assertTrue($client->verifyNotify($params));
    }

    /**
     * 生成测试使用的 RSA 密钥对。
     *
     * @return array{0:string,1:string}
     */
    private static function keyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($resource);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);

        return [$privateKey, (string)$details['key']];
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
