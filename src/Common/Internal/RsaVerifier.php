<?php

declare(strict_types=1);

namespace We\Common\Internal;

use We\Common\Exception\SignatureException;
use We\Common\Provider\TrustMaterialProviderInterface;
use We\Common\Support\CredentialValidator;

/**
 * 使用通道信任材料验证 Base64 RSA 签名。
 *
 * @internal
 */
final class RsaVerifier
{
    public static function verify(
        TrustMaterialProviderInterface $trust,
        string $channel,
        string $keyId,
        string $message,
        string $signature,
        string $materialLabel,
        string $failureMessage,
        int|string $algorithm = OPENSSL_ALGO_SHA256,
    ): void {
        $decoded = base64_decode($signature, true);
        $key = CredentialValidator::loadPublicKey(
            $trust->publicKey($channel, $keyId),
            $materialLabel,
            exceptionClass: SignatureException::class,
        );
        if ($decoded === false || @openssl_verify($message, $decoded, $key, $algorithm) !== 1) {
            throw new SignatureException($failureMessage, channel: $channel);
        }
    }
}
