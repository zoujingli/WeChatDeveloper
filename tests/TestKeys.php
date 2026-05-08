<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Tests;

/**
 * 测试密钥夹具。
 */
final class TestKeys
{
    /** @var null|array{0:string,1:string} */
    private static ?array $keyPair = null;

    /**
     * 返回测试用 EncodingAESKey。
     */
    public static function encodingAesKey(): string
    {
        return 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';
    }

    /**
     * 返回测试用 RSA 私钥。
     */
    public static function privateKey(): string
    {
        return self::keyPair()[0];
    }

    /**
     * 返回测试用 RSA 公钥。
     */
    public static function publicKey(): string
    {
        return self::keyPair()[1];
    }

    /**
     * 生成并缓存测试使用的 RSA 密钥对。
     *
     * @return array{0:string,1:string}
     */
    public static function keyPair(): array
    {
        if (self::$keyPair !== null) {
            return self::$keyPair;
        }

        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($resource === false) {
            throw new \RuntimeException('Unable to create test RSA key pair.');
        }
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        if (!is_array($details)) {
            throw new \RuntimeException('Unable to read test RSA public key.');
        }

        return self::$keyPair = [$privateKey, (string)$details['key']];
    }
}
