<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Support;

use We\Exception\WechatException;

/**
 * 密钥、证书与平台安全参数校验工具。
 */
final class CredentialValidator
{
    /**
     * 校验微信服务器配置 Token。
     */
    public static function assertWechatToken(string $token, string $field = 'token'): void
    {
        if (preg_match('/^[A-Za-z0-9]{3,32}$/', $token) !== 1) {
            throw new WechatException($field . ' 必须是 3-32 位英文或数字');
        }
    }

    /**
     * 校验微信消息 EncodingAESKey。
     */
    public static function assertEncodingAesKey(string $encodingAesKey, string $field = 'EncodingAESKey'): void
    {
        if (strlen($encodingAesKey) !== 43) {
            throw new WechatException($field . ' 必须是 43 位有效字符串');
        }
        $key = base64_decode($encodingAesKey . '=', true);
        if ($key === false || strlen($key) !== 32) {
            throw new WechatException($field . ' 必须是 43 位有效字符串');
        }
    }

    /**
     * 校验微信支付 APIv3 密钥。
     */
    public static function assertApiV3Key(string $apiV3Key): void
    {
        if (strlen($apiV3Key) !== 32) {
            throw new WechatException('apiV3Key 必须是 32 字节字符串');
        }
    }

    /**
     * 校验 RSA 私钥。
     */
    public static function assertPrivateKey(string $privateKey, string $field, bool $wrapRawKey = false): void
    {
        $resource = openssl_pkey_get_private(self::normalizePrivateKey($privateKey, $wrapRawKey));
        if ($resource === false) {
            throw new WechatException($field . ' 格式无效');
        }
    }

    /**
     * 校验 RSA 公钥或证书。
     */
    public static function assertPublicKey(string $publicKey, string $field, bool $wrapRawKey = false): void
    {
        $resource = openssl_pkey_get_public(self::normalizePublicKey($publicKey, $wrapRawKey));
        if ($resource === false) {
            throw new WechatException($field . ' 格式无效');
        }
    }

    /**
     * 将私钥规范化为 PEM 字符串；支付宝支持传入无头尾的密钥正文。
     */
    public static function normalizePrivateKey(string $privateKey, bool $wrapRawKey = false): string
    {
        $key = trim($privateKey);
        if ($key === '' || str_contains($key, 'BEGIN')) {
            return $key;
        }
        if (!$wrapRawKey) {
            return $key;
        }

        return "-----BEGIN PRIVATE KEY-----\n" . chunk_split($key, 64, "\n") . '-----END PRIVATE KEY-----';
    }

    /**
     * 将公钥规范化为 PEM 字符串；支付宝支持传入无头尾的公钥正文。
     */
    public static function normalizePublicKey(string $publicKey, bool $wrapRawKey = false): string
    {
        $key = trim($publicKey);
        if ($key === '' || str_contains($key, 'BEGIN')) {
            return $key;
        }
        if (!$wrapRawKey) {
            return $key;
        }

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($key, 64, "\n") . '-----END PUBLIC KEY-----';
    }
}
