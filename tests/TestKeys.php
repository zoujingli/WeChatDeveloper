<?php

declare(strict_types=1);

namespace We\Tests;

/**
 * 测试密钥夹具。
 */
final class TestKeys
{
    /** @var null|array{0:string,1:string} */
    private static ?array $keyPair = null;

    /** @var null|array{0:string,1:string} */
    private static ?array $platformKeyPair = null;

    private static ?string $ecPrivateKey = null;

    private static ?string $pkcs1PrivateKey = null;

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

    public static function privateKeyBody(): string
    {
        return self::pemBody(self::privateKey());
    }

    public static function publicKeyBody(): string
    {
        return self::pemBody(self::publicKey());
    }

    public static function pkcs1PrivateKeyBody(): string
    {
        if (self::$pkcs1PrivateKey !== null) {
            return self::pemBody(self::$pkcs1PrivateKey);
        }
        $resource = openssl_pkey_get_private(self::privateKey());
        $details = $resource === false ? false : openssl_pkey_get_details($resource);
        $rsa = is_array($details) && is_array($details['rsa'] ?? null) ? $details['rsa'] : null;
        if (!is_array($rsa)) {
            throw self::openSslFailure('Unable to read test RSA private-key details.');
        }
        $der = self::asn1Sequence(
            self::asn1Integer("\0"),
            self::asn1Integer((string)$rsa['n']),
            self::asn1Integer((string)$rsa['e']),
            self::asn1Integer((string)$rsa['d']),
            self::asn1Integer((string)$rsa['p']),
            self::asn1Integer((string)$rsa['q']),
            self::asn1Integer((string)$rsa['dmp1']),
            self::asn1Integer((string)$rsa['dmq1']),
            self::asn1Integer((string)$rsa['iqmp']),
        );
        self::$pkcs1PrivateKey = "-----BEGIN RSA PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . '-----END RSA PRIVATE KEY-----';

        return self::pemBody(self::$pkcs1PrivateKey);
    }

    public static function ecPrivateKey(): string
    {
        if (self::$ecPrivateKey !== null) {
            return self::$ecPrivateKey;
        }
        self::clearOpenSslErrors();
        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if ($resource === false || !openssl_pkey_export($resource, $privateKey)) {
            throw self::openSslFailure('Unable to create test EC private key.');
        }

        return self::$ecPrivateKey = $privateKey;
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

        return self::$keyPair = self::generateKeyPair();
    }

    /**
     * 返回与商户/应用密钥相互独立的平台测试 RSA 密钥对。
     *
     * @return array{0:string,1:string}
     */
    public static function platformKeyPair(): array
    {
        if (self::$platformKeyPair !== null) {
            return self::$platformKeyPair;
        }

        return self::$platformKeyPair = self::generateKeyPair();
    }

    /**
     * @return array{0:string,1:string}
     */
    private static function generateKeyPair(): array
    {
        self::clearOpenSslErrors();
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($resource === false) {
            throw self::openSslFailure('Unable to create test RSA key pair.');
        }
        if (!openssl_pkey_export($resource, $privateKey)) {
            throw self::openSslFailure('Unable to export test RSA private key.');
        }
        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || !is_string($details['key'] ?? null)) {
            throw self::openSslFailure('Unable to read test RSA public key.');
        }

        return [$privateKey, $details['key']];
    }

    private static function clearOpenSslErrors(): void
    {
        while (openssl_error_string() !== false);
    }

    private static function openSslFailure(string $message): \RuntimeException
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        $details = $errors === [] ? 'OpenSSL returned no diagnostic details.' : implode(' | ', $errors);
        $config = getenv('OPENSSL_CONF');

        return new \RuntimeException(sprintf(
            '%s %s OPENSSL_CONF=%s',
            $message,
            $details,
            is_string($config) && $config !== '' ? $config : '(not set)',
        ));
    }

    private static function pemBody(string $pem): string
    {
        return (string)preg_replace('/-----BEGIN [^-]+-----|-----END [^-]+-----|\s+/', '', $pem);
    }

    private static function asn1Integer(string $value): string
    {
        $value = ltrim($value, "\0");
        if ($value === '') {
            $value = "\0";
        } elseif ((ord($value[0]) & 0x80) !== 0) {
            $value = "\0" . $value;
        }

        return "\x02" . self::asn1Length(strlen($value)) . $value;
    }

    private static function asn1Sequence(string ...$values): string
    {
        $value = implode('', $values);

        return "\x30" . self::asn1Length(strlen($value)) . $value;
    }

    private static function asn1Length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }
}
