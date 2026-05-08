<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Config\WechatPaymentConfig;
use We\Exception\SignatureException;
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
    /**
     * 测试微信支付回调验签使用原始请求体。
     */
    public function testDecryptNotificationUsesRawBodyForSignature(): void
    {
        [$platformPrivateKey, $platformPublicKey] = self::keyPair();
        $apiV3Key = str_repeat('k', 32);
        $nonce = '123456789012';
        $aad = 'transaction';
        $plain = '{"out_trade_no":"T202605040001","trade_state":"SUCCESS"}';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $apiV3Key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
        self::assertIsString($cipher);

        $rawBody = "{\n  \"id\": \"notify-id\",\n  \"resource\": {\n    \"ciphertext\": \"" . base64_encode($cipher . $tag) . "\",\n    \"nonce\": \"{$nonce}\",\n    \"associated_data\": \"{$aad}\"\n  }\n}";
        $timestamp = '1777600000';
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
        [$platformPrivateKey, $platformPublicKey] = self::keyPair();
        $rawBody = '{"resource":{"ciphertext":"invalid","nonce":"nonce"}}';
        $timestamp = '1777600000';
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
        [$platformPrivateKey, $platformPublicKey] = self::keyPair();
        $rawBody = '{"resource":"invalid"}';
        $timestamp = '1777600000';
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
}
