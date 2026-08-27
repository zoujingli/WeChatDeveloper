<?php

declare(strict_types=1);

namespace We\Common\Support;

use We\Common\Exception\ConfigurationException;
use We\Common\Exception\SdkException;

/**
 * 规范化并校验 RSA 密钥或证书。
 *
 * @internal
 */
final class CredentialValidator
{
    /**
     * 校验 RSA 私钥。
     *
     * @param class-string<SdkException> $exceptionClass
     */
    public static function assertPrivateKey(
        string $privateKey,
        string $field,
        bool $wrapRawKey = false,
        string $exceptionClass = ConfigurationException::class,
    ): void {
        self::loadPrivateKey($privateKey, $field, $wrapRawKey, $exceptionClass);
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
        string $exceptionClass = ConfigurationException::class,
    ): void {
        self::loadPublicKey($publicKey, $field, $wrapRawKey, $exceptionClass);
    }

    /**
     * 无警告地加载 RSA 私钥，并把无效材料转换为指定 SDK 异常。
     *
     * @param class-string<SdkException> $exceptionClass
     */
    public static function loadPrivateKey(
        string $privateKey,
        string $field,
        bool $wrapRawKey = false,
        string $exceptionClass = ConfigurationException::class,
    ): \OpenSSLAsymmetricKey {
        return self::assertRsa(
            @openssl_pkey_get_private(self::normalizePrivateKey($privateKey, $wrapRawKey)),
            $field,
            $exceptionClass,
        );
    }

    /**
     * 无警告地加载 RSA 公钥或证书，并把无效材料转换为指定 SDK 异常。
     *
     * @param class-string<SdkException> $exceptionClass
     */
    public static function loadPublicKey(
        string $publicKey,
        string $field,
        bool $wrapRawKey = false,
        string $exceptionClass = ConfigurationException::class,
    ): \OpenSSLAsymmetricKey {
        return self::assertRsa(
            @openssl_pkey_get_public(self::normalizePublicKey($publicKey, $wrapRawKey)),
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
            if (@openssl_pkey_get_private($candidate) !== false) {
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
     * @param false|\OpenSSLAsymmetricKey $resource
     * @param class-string<SdkException> $exceptionClass
     */
    private static function assertRsa(mixed $resource, string $field, string $exceptionClass): \OpenSSLAsymmetricKey
    {
        if ($resource === false) {
            throw new $exceptionClass($field . ' 格式无效');
        }
        $details = @openssl_pkey_get_details($resource);
        if (!is_array($details) || ($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new $exceptionClass($field . ' 必须是 RSA 密钥或证书');
        }

        return $resource;
    }
}
