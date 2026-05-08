<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Support;

use We\Exception\SignatureException;

/**
 * 签名与验签工具集合。
 *
 * 覆盖微信消息 SHA1 签名、微信支付 APIv3 商户 RSA-SHA256 签名和平台证书/公钥验签。
 */
final class Signature
{
    /**
     * 生成微信服务器消息签名：参数按字典序排序后拼接并计算 SHA1。
     *
     * @param array<int,string> $items
     */
    public static function sha1(array $items): string
    {
        sort($items, SORT_STRING);

        return sha1(implode('', $items));
    }

    /**
     * 校验微信服务器消息签名。
     *
     * @param array<int,string> $items
     */
    public static function assertSha1(string $expected, array $items): void
    {
        $actual = self::sha1($items);
        if (!hash_equals($actual, $expected)) {
            throw new SignatureException('微信回调签名验证失败', 0, null, ['expected' => $expected, 'actual' => $actual]);
        }
    }

    /**
     * 使用微信支付商户私钥生成 RSA-SHA256 签名。
     */
    public static function paymentV3Sign(string $privateKey, string $message): string
    {
        $key = openssl_pkey_get_private($privateKey);
        if ($key === false) {
            throw new SignatureException('微信支付商户私钥无效');
        }
        if (!openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new SignatureException('微信支付签名生成失败');
        }

        return base64_encode($signature);
    }

    /**
     * 使用微信支付平台证书或平台公钥验证 RSA-SHA256 签名。
     */
    public static function verifyPaymentV3(string $publicKey, string $message, string $signature): bool
    {
        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            throw new SignatureException('微信支付平台公钥或证书无效');
        }
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        return openssl_verify($message, $decoded, $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
