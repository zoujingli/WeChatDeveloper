<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response;
use We\Alipay\AliPayConfig;
use We\Alipay\AliRestConfig;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Wechat\WxPayConfig;

/**
 * 平台通道协议测试夹具。
 *
 * @internal
 */
final class ProtocolFixtures
{
    public static function wxPayConfig(): WxPayConfig
    {
        return new WxPayConfig(
            'wx_app',
            'mch_id',
            new PemSigningKeyProvider('merchant-serial', TestKeys::privateKey()),
            new StaticTrustMaterialProvider([
                'wechat.payment' => ['platform-serial' => TestKeys::platformKeyPair()[1]],
            ]),
        );
    }

    public static function aliPayConfig(string $format = 'JSON'): AliPayConfig
    {
        return new AliPayConfig(
            'ali_app',
            new PemSigningKeyProvider('application', TestKeys::privateKey()),
            new StaticTrustMaterialProvider([
                'alipay.gateway' => ['default' => TestKeys::platformKeyPair()[1]],
            ]),
            format: $format,
        );
    }

    public static function aliRestConfig(): AliRestConfig
    {
        return new AliRestConfig(
            'ali_app',
            new PemSigningKeyProvider('application', TestKeys::privateKey()),
            new StaticTrustMaterialProvider([
                'alipay.rest' => ['default' => TestKeys::platformKeyPair()[1]],
            ]),
        );
    }

    public static function wxPayResponse(int $status, string $body, ?string $timestamp = null): Response
    {
        $timestamp ??= (string)time();
        $nonce = 'response-nonce';
        $signer = new PemSigningKeyProvider('platform-serial', TestKeys::platformKeyPair()[0]);

        return new Response($status, [
            'Content-Type' => 'application/json',
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $nonce,
            'Wechatpay-Serial' => 'platform-serial',
            'Wechatpay-Signature' => $signer->sign("{$timestamp}\n{$nonce}\n{$body}\n"),
        ], $body);
    }

    public static function aliRestResponse(int $status, string $body): Response
    {
        $timestamp = '1778200000000';
        $nonce = 'response-nonce';
        $signer = new PemSigningKeyProvider('default', TestKeys::platformKeyPair()[0]);

        return new Response($status, [
            'Content-Type' => 'application/json',
            'alipay-timestamp' => $timestamp,
            'alipay-nonce' => $nonce,
            'alipay-sn' => 'default',
            'alipay-signature' => $signer->sign("{$timestamp}\n{$nonce}\n{$body}\n"),
        ], $body);
    }

    /** @param array<string,mixed> $node */
    public static function aliPayResponse(string $method, array $node, int $status = 200): Response
    {
        $name = str_replace('.', '_', $method) . '_response';
        $rawNode = json_encode($node, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signer = new PemSigningKeyProvider('default', TestKeys::platformKeyPair()[0]);
        $body = '{"' . $name . '":' . $rawNode . ',"sign":"' . $signer->sign($rawNode) . '"}';

        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }
}
