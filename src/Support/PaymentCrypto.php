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
 * 微信支付 APIv3 通知资源解密工具。
 *
 * 使用商户 APIv3 密钥对通知 resource.ciphertext 执行 AES-256-GCM 解密，并解析明文 JSON。
 */
final class PaymentCrypto
{
    /**
     * 解密微信支付 APIv3 通知中的 resource 字段。
     *
     * @param array<string,mixed> $resource
     * @return array<string,mixed>
     */
    public static function decryptResource(string $apiV3Key, array $resource): array
    {
        CredentialValidator::assertApiV3Key($apiV3Key);
        $ciphertextValue = $resource['ciphertext'] ?? null;
        $nonce = $resource['nonce'] ?? null;
        $associatedData = $resource['associated_data'] ?? '';
        if (!is_string($ciphertextValue) || $ciphertextValue === '') {
            throw new WechatException('微信支付回调资源字段缺失: ciphertext');
        }
        if (!is_string($nonce) || $nonce === '') {
            throw new WechatException('微信支付回调资源字段缺失: nonce');
        }
        if (!is_string($associatedData)) {
            throw new WechatException('微信支付回调资源字段无效: associated_data');
        }
        $ciphertext = base64_decode($ciphertextValue, true);
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
            $nonce,
            $tag,
            $associatedData
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
