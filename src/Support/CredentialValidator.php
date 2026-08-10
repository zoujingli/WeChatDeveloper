<?php

declare(strict_types=1);

namespace We\Support;

use We\Exception\SdkException;
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
     *
     * @param class-string<SdkException> $exceptionClass
     */
    public static function assertPrivateKey(
        string $privateKey,
        string $field,
        bool $wrapRawKey = false,
        string $exceptionClass = WechatException::class,
    ): void {
        self::assertRsa(
            openssl_pkey_get_private(self::normalizePrivateKey($privateKey, $wrapRawKey)),
            $field,
            $exceptionClass,
        );
    }

    /**
     * 校验 RSA 公钥或证书。
     *
     * @param class-string<SdkException> $exceptionClass
     */
    public static function assertPublicKey(
        string $publicKey,
        string $field,
        bool $wrapRawKey = false,
        string $exceptionClass = WechatException::class,
    ): void {
        self::assertRsa(
            openssl_pkey_get_public(self::normalizePublicKey($publicKey, $wrapRawKey)),
            $field,
            $exceptionClass,
        );
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

        foreach (['PRIVATE KEY', 'RSA PRIVATE KEY'] as $label) {
            $candidate = self::wrapPem($key, $label);
            if (openssl_pkey_get_private($candidate) !== false) {
                return $candidate;
            }
        }

        return self::wrapPem($key, 'PRIVATE KEY');
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

        return self::wrapPem($key, 'PUBLIC KEY');
    }

    private static function wrapPem(string $body, string $label): string
    {
        return "-----BEGIN {$label}-----\n" . chunk_split($body, 64, "\n") . "-----END {$label}-----";
    }

    /**
     * @param false|\OpenSSLAsymmetricKey|resource $resource
     * @param class-string<SdkException> $exceptionClass
     */
    private static function assertRsa(mixed $resource, string $field, string $exceptionClass): void
    {
        if ($resource === false) {
            throw new $exceptionClass($field . ' 格式无效');
        }
        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new $exceptionClass($field . ' 必须是 RSA 密钥或证书');
        }
    }
}
