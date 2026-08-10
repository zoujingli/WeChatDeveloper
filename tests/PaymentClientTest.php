<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Config\WechatPaymentConfig;
use We\Exception\ApiException;
use We\Exception\SignatureException;
use We\Exception\TransportException;
use We\Exception\WechatException;
use We\Platform\Wechat\PaymentClient as WechatPaymentClient;
use We\Support\Signature;

/**
 * 微信支付 APIv3 通知验签与解密测试用例。
 * @internal
 */
#[CoversClass(WechatPaymentClient::class)]
final class PaymentClientTest extends TestCase
{
    public function testRequestReturnsOnlySignedPlatformResponse(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $body = "{\n  \"prepay_id\": \"wx-prepay-1\"\n}";
        $response = WechatPaymentResponse::signed(200, $body, $platformPrivateKey);
        $client = self::paymentClient($response, $platformPublicKey);

        $data = $client->post('v3/pay/transactions/jsapi', ['description' => 'signed response']);

        self::assertSame('wx-prepay-1', $data['prepay_id']);
    }

    public function testRequestRejectsUnsignedPlatformResponse(): void
    {
        $client = self::paymentClient(
            new Response(200, [], '{"prepay_id":"unsigned"}'),
            TestKeys::platformKeyPair()[1],
        );

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('响应');

        $client->post('v3/pay/transactions/jsapi', ['description' => 'unsigned response']);
    }

    public function testRequestRejectsInvalidPlatformSignature(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $response = WechatPaymentResponse::signed(200, '{"prepay_id":"tampered"}', $platformPrivateKey)
            ->withHeader('Wechatpay-Signature', base64_encode('invalid-signature'));
        $client = self::paymentClient($response, $platformPublicKey);

        $this->expectException(SignatureException::class);

        $client->post('v3/pay/transactions/jsapi');
    }

    public function testRequestRejectsSignedNonJsonResponse(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $client = self::paymentClient(
            WechatPaymentResponse::signed(200, 'not-json', $platformPrivateKey),
            $platformPublicKey,
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('JSON');

        $client->get('v3/certificates');
    }

    public function testRequestExposesTransportFailureType(): void
    {
        $failure = new ConnectException(
            'connection failed',
            new Request('GET', 'https://api.mch.weixin.qq.com/v3/certificates'),
        );
        $client = self::paymentClient($failure, TestKeys::platformKeyPair()[1]);

        $this->expectException(TransportException::class);

        $client->get('v3/certificates');
    }

    public function testNotificationRejectsTimestampOutsideDefaultWindow(): void
    {
        [$client, $headers, $body] = self::notificationFixture(time() - 301);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('时间戳');

        $client->post('decrypt_notification', [], ['headers' => $headers, 'raw_body' => $body]);
    }

    public function testNotificationRejectsFutureTimestampOutsideDefaultWindow(): void
    {
        [$client, $headers, $body] = self::notificationFixture(time() + 301);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('时间戳');

        $client->post('decrypt_notification', [], ['headers' => $headers, 'raw_body' => $body]);
    }

    public function testNotificationAcceptsConfiguredFreshnessWindow(): void
    {
        [$client, $headers, $body] = self::notificationFixture(time() - 600, 900);

        $data = $client->post('decrypt_notification', [], ['headers' => $headers, 'raw_body' => $body]);

        self::assertSame('T-FRESHNESS', $data['out_trade_no']);
    }

    public function testNotificationFreshnessCanBeExplicitlyDisabled(): void
    {
        [$client, $headers, $body] = self::notificationFixture(1, 0);

        $data = $client->post('decrypt_notification', [], ['headers' => $headers, 'raw_body' => $body]);

        self::assertSame('T-FRESHNESS', $data['out_trade_no']);
    }

    public function testDownloadBillCompletesVerifiedTwoStageFlow(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $metadataBody = '{"download_url":"https://download.example.com/bill.csv?token=signed"}';
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            WechatPaymentResponse::signed(200, $metadataBody, $platformPrivateKey),
            new Response(200, ['Content-Type' => 'text/csv'], "trade_no,amount\nT1,1\n"),
        ]));
        $stack->push(Middleware::history($history));
        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.mch.weixin.qq.com/']);
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::privateKey(),
            platformPublicKey: $platformPublicKey,
            platformSerial: 'platform-serial',
        ), $http);

        $response = $client->downloadBill('v3/bill/tradebill', ['bill_date' => '2026-08-10']);

        self::assertSame("trade_no,amount\nT1,1\n", (string)$response->getBody());
        self::assertCount(2, $history);
        self::assertSame('api.mch.weixin.qq.com', $history[0]['request']->getUri()->getHost());
        self::assertSame('download.example.com', $history[1]['request']->getUri()->getHost());
    }

    public function testDownloadBillRejectsNonHttpsDownloadUrl(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $metadataBody = '{"download_url":"http://127.0.0.1/internal"}';
        $client = self::paymentClient(
            WechatPaymentResponse::signed(200, $metadataBody, $platformPrivateKey),
            $platformPublicKey,
        );

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('HTTPS');

        $client->downloadBill('v3/bill/tradebill', ['bill_date' => '2026-08-10']);
    }

    public function testDownloadBillDoesNotFollowRedirects(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $metadataBody = '{"download_url":"https://download.example.com/bill.csv"}';
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            WechatPaymentResponse::signed(200, $metadataBody, $platformPrivateKey),
            new Response(302, ['Location' => 'http://127.0.0.1/internal']),
            new Response(200, [], 'unsafe redirect result'),
        ]));
        $stack->push(Middleware::history($history));
        $http = new Client(['handler' => $stack, 'base_uri' => 'https://api.mch.weixin.qq.com/']);
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::privateKey(),
            platformPublicKey: $platformPublicKey,
            platformSerial: 'platform-serial',
        ), $http);

        try {
            $client->downloadBill('v3/bill/tradebill');
            self::fail('Expected redirect response to be rejected.');
        } catch (ApiException $exception) {
            self::assertSame(302, $exception->getCode());
            self::assertCount(2, $history);
        }
    }

    /**
     * 测试微信支付回调验签使用原始请求体。
     */
    public function testDecryptNotificationUsesRawBodyForSignature(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $apiV3Key = str_repeat('k', 32);
        $nonce = '123456789012';
        $aad = 'transaction';
        $plain = '{"out_trade_no":"T202605040001","trade_state":"SUCCESS"}';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $apiV3Key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        self::assertIsString($cipher);

        $rawBody = "{\n  \"id\": \"notify-id\",\n  \"resource\": {\n    \"ciphertext\": \"" . base64_encode($cipher . $tag) . "\",\n    \"nonce\": \"{$nonce}\",\n    \"associated_data\": \"{$aad}\"\n  }\n}";
        $timestamp = (string)time();
        $notifyNonce = 'notify-nonce';
        $headers = [
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $notifyNonce,
            'Wechatpay-Serial' => 'platform-serial',
            'Wechatpay-Signature' => Signature::paymentV3Sign($platformPrivateKey, "{$timestamp}\n{$notifyNonce}\n{$rawBody}\n"),
        ];
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            $apiV3Key,
            'merchant-serial',
            TestKeys::privateKey(),
            '',
            $platformPublicKey,
            'platform-serial',
        ));

        $data = $client->post('decrypt_notification', [], [
            'headers' => $headers,
            'raw_body' => $rawBody,
        ]);

        self::assertSame('T202605040001', $data['out_trade_no']);
        self::assertSame('SUCCESS', $data['trade_state']);
    }

    /**
     * 测试微信支付回调平台序列号不匹配时拒绝处理。
     */
    public function testDecryptNotificationRejectsPlatformSerialMismatch(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $rawBody = '{"resource":{"ciphertext":"invalid","nonce":"nonce"}}';
        $timestamp = (string)time();
        $notifyNonce = 'notify-nonce';
        $headers = [
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $notifyNonce,
            'Wechatpay-Serial' => 'other-serial',
            'Wechatpay-Signature' => Signature::paymentV3Sign($platformPrivateKey, "{$timestamp}\n{$notifyNonce}\n{$rawBody}\n"),
        ];
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::privateKey(),
            '',
            $platformPublicKey,
            'platform-serial',
        ));

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('序列号');

        $client->post('decrypt_notification', [], [
            'headers' => $headers,
            'raw_body' => $rawBody,
        ]);
    }

    /**
     * 测试微信支付回调 resource 结构异常时抛出 SDK 异常而不是 TypeError。
     */
    public function testDecryptNotificationRejectsInvalidResourceShape(): void
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $rawBody = '{"resource":"invalid"}';
        $timestamp = (string)time();
        $notifyNonce = 'notify-nonce';
        $headers = [
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $notifyNonce,
            'Wechatpay-Serial' => 'platform-serial',
            'Wechatpay-Signature' => Signature::paymentV3Sign($platformPrivateKey, "{$timestamp}\n{$notifyNonce}\n{$rawBody}\n"),
        ];
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::privateKey(),
            '',
            $platformPublicKey,
            'platform-serial',
        ));

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('resource');

        $client->post('decrypt_notification', [], [
            'headers' => $headers,
            'raw_body' => $rawBody,
        ]);
    }

    private static function paymentClient(Response|\Throwable $result, string $platformPublicKey): WechatPaymentClient
    {
        $http = new Client([
            'handler' => HandlerStack::create(new MockHandler([$result])),
            'base_uri' => 'https://api.mch.weixin.qq.com/',
        ]);

        return new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::privateKey(),
            platformPublicKey: $platformPublicKey,
            platformSerial: 'platform-serial',
        ), $http);
    }

    /**
     * @return array{0:WechatPaymentClient,1:array<string,string>,2:string}
     */
    private static function notificationFixture(int $timestamp, int $freshnessWindow = 300): array
    {
        [$platformPrivateKey, $platformPublicKey] = TestKeys::platformKeyPair();
        $apiV3Key = str_repeat('k', 32);
        $nonce = '123456789012';
        $aad = 'transaction';
        $plain = '{"out_trade_no":"T-FRESHNESS"}';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $apiV3Key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        self::assertIsString($cipher);
        $body = '{"resource":{"ciphertext":"' . base64_encode($cipher . $tag)
            . '","nonce":"' . $nonce . '","associated_data":"' . $aad . '"}}';
        $timestampValue = (string)$timestamp;
        $notifyNonce = 'freshness-nonce';
        $headers = [
            'Wechatpay-Timestamp' => $timestampValue,
            'Wechatpay-Nonce' => $notifyNonce,
            'Wechatpay-Serial' => 'platform-serial',
            'Wechatpay-Signature' => Signature::paymentV3Sign(
                $platformPrivateKey,
                "{$timestampValue}\n{$notifyNonce}\n{$body}\n",
            ),
        ];
        $client = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            $apiV3Key,
            'merchant-serial',
            TestKeys::privateKey(),
            platformPublicKey: $platformPublicKey,
            platformSerial: 'platform-serial',
            notificationToleranceSeconds: $freshnessWindow,
        ));

        return [$client, $headers, $body];
    }
}
