<?php

declare(strict_types=1);

namespace We\Support;

use We\Exception\SignatureException;

final class Signature
{
    /**
     * 微信公众号与开放平台回调签名，参数按字典序拼接后 SHA1。
     *
     * @param array<int,string> $items
     */
    public static function sha1(array $items): string
    {
        sort($items, SORT_STRING);

        return sha1(implode('', $items));
    }

    /**
     * @param array<int,string> $items
     */
    public static function assertSha1(string $expected, array $items): void
    {
        $actual = self::sha1($items);
        if (!hash_equals($actual, $expected)) {
            throw new SignatureException('微信回调签名验证失败', 0, null, ['expected' => $expected, 'actual' => $actual]);
        }
    }

    public static function payV3Sign(string $privateKey, string $message): string
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

    public static function verifyPayV3(string $publicKey, string $message, string $signature): bool
    {
        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            throw new SignatureException('微信支付平台公钥或证书无效');
        }

        return openssl_verify($message, base64_decode($signature, true) ?: '', $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
