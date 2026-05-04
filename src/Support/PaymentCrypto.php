<?php

declare(strict_types=1);

namespace We\Support;

use We\Exception\WechatException;

final class PaymentCrypto
{
    /**
     * @param array{ciphertext:string,nonce:string,associated_data?:string} $resource
     * @return array<string,mixed>
     */
    public static function decryptResource(string $apiV3Key, array $resource): array
    {
        foreach (['ciphertext', 'nonce'] as $field) {
            if (!isset($resource[$field]) || !is_string($resource[$field]) || $resource[$field] === '') {
                throw new WechatException('微信支付回调资源字段缺失: ' . $field);
            }
        }
        $ciphertext = base64_decode($resource['ciphertext'], true);
        if ($ciphertext === false || strlen($ciphertext) <= 16) {
            throw new WechatException('微信支付回调密文无效');
        }

        $tag = substr($ciphertext, -16);
        $ciphertext = substr($ciphertext, 0, -16);
        $plain = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $apiV3Key,
            OPENSSL_RAW_DATA,
            $resource['nonce'],
            $tag,
            (string)($resource['associated_data'] ?? '')
        );
        if (!is_string($plain) || $plain === '') {
            throw new WechatException('微信支付回调解密失败');
        }

        $data = json_decode($plain, true);
        if (!is_array($data)) {
            throw new WechatException('微信支付回调明文 JSON 无效');
        }

        return $data;
    }
}
