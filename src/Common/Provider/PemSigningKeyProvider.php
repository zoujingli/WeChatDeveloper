<?php

declare(strict_types=1);

namespace We\Common\Provider;

use We\Common\Exception\ConfigurationException;
use We\Common\Exception\SignatureException;
use We\Common\Support\CredentialValidator;

/** 使用本地 PEM RSA 私钥的签名 Provider。 */
final class PemSigningKeyProvider implements SigningKeyProviderInterface
{
    private readonly string $privateKey;

    /**
     * 使用密钥 ID 和 PEM 私钥创建 Provider；`wrapRawKey` 仅用于无 PEM 边界的 Base64 密钥。
     */
    public function __construct(
        private readonly string $id,
        string $privateKey,
        bool $wrapRawKey = false,
    ) {
        if (trim($this->id) === '' || preg_match('/[\x00-\x20\x7F"\\\]/', $this->id) === 1) {
            throw new ConfigurationException('签名密钥 ID 无效');
        }
        $this->privateKey = CredentialValidator::normalizePrivateKey($privateKey, $wrapRawKey);
        CredentialValidator::assertPrivateKey(
            $this->privateKey,
            '签名私钥',
            exceptionClass: ConfigurationException::class,
        );
    }

    public function keyId(): string
    {
        return $this->id;
    }

    public function sign(string $message, int|string $algorithm = OPENSSL_ALGO_SHA256): string
    {
        $key = CredentialValidator::loadPrivateKey(
            $this->privateKey,
            '签名私钥',
            exceptionClass: SignatureException::class,
        );
        if (@openssl_sign($message, $signature, $key, $algorithm) !== true) {
            throw new SignatureException('RSA 签名生成失败');
        }

        return base64_encode($signature);
    }
}
