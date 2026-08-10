<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response;
use We\Support\Signature;

/**
 * 微信支付平台签名响应测试夹具。
 */
final class WechatPaymentResponse
{
    public static function signed(
        int $status,
        string $body,
        string $platformPrivateKey,
        string $serial = 'platform-serial',
    ): Response {
        $timestamp = '1778200000';
        $nonce = 'response-nonce';

        return new Response($status, [
            'Wechatpay-Timestamp' => $timestamp,
            'Wechatpay-Nonce' => $nonce,
            'Wechatpay-Serial' => $serial,
            'Wechatpay-Signature' => Signature::paymentV3Sign(
                $platformPrivateKey,
                "{$timestamp}\n{$nonce}\n{$body}\n",
            ),
        ], $body);
    }
}
