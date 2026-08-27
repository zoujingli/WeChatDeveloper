<?php

declare(strict_types=1);

namespace We\Common\Internal;

use We\Common\Exception\ConfigurationException;
use We\Common\Exception\SignatureException;

/**
 * 校验外部 Provider 返回的协议值。
 *
 * @internal
 */
final class ProviderValue
{
    public static function token(string $value, string $channel): string
    {
        if (trim($value) === '' || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw new ConfigurationException('Token Provider 返回值无效', channel: $channel);
        }

        return $value;
    }

    public static function signingKeyId(string $value, string $channel): string
    {
        if (trim($value) === '' || preg_match('/[\x00-\x20\x7F"\\\]/', $value) === 1) {
            throw new SignatureException('签名 Provider 返回的密钥 ID 无效', channel: $channel);
        }

        return $value;
    }

    public static function signature(string $value, string $channel): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || $decoded === '' || preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
            throw new SignatureException('签名 Provider 返回值不是有效 Base64 签名', channel: $channel);
        }

        return $value;
    }
}
