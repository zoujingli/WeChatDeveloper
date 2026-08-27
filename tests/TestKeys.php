<?php

declare(strict_types=1);

namespace We\Tests;

/**
 * 测试密钥夹具。
 *
 * @internal
 */
final class TestKeys
{
    /** @var null|array{0:string,1:string} */
    private static ?array $keyPair = null;

    /** @var null|array{0:string,1:string} */
    private static ?array $platformKeyPair = null;

    public static function privateKey(): string
    {
        return self::keyPair()[0];
    }

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
        $privateKey = null;
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($resource === false) {
            $privateKey = self::generatePrivateKeyWithCli();
            $resource = openssl_pkey_get_private($privateKey);
            if ($resource === false) {
                throw self::openSslFailure('无法创建测试 RSA 密钥对。');
            }
        }
        if ($privateKey === null && !openssl_pkey_export($resource, $privateKey)) {
            throw self::openSslFailure('无法导出测试 RSA 私钥。');
        }
        if (!is_string($privateKey)) {
            throw self::openSslFailure('无法读取测试 RSA 私钥。');
        }
        $details = openssl_pkey_get_details($resource);
        if (!is_array($details) || !is_string($details['key'] ?? null)) {
            throw self::openSslFailure('无法读取测试 RSA 公钥。');
        }

        return [$privateKey, $details['key']];
    }

    private static function generatePrivateKeyWithCli(): string
    {
        $pipes = [];
        $process = proc_open(
            ['openssl', 'genpkey', '-algorithm', 'RSA', '-pkeyopt', 'rsa_keygen_bits:2048'],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );
        if (!is_resource($process)) {
            throw self::openSslFailure('无法启动 OpenSSL CLI 生成测试 RSA 密钥。');
        }
        fclose($pipes[0]);
        $privateKey = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0 || !is_string($privateKey) || trim($privateKey) === '') {
            throw new \RuntimeException('OpenSSL CLI 生成测试密钥失败：' . (is_string($error) ? trim($error) : '未知错误'));
        }

        return $privateKey;
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
        $details = $errors === [] ? 'OpenSSL 未返回诊断详情。' : implode(' | ', $errors);
        $config = getenv('OPENSSL_CONF');

        return new \RuntimeException(sprintf(
            '%s %s OPENSSL_CONF=%s',
            $message,
            $details,
            is_string($config) && $config !== '' ? $config : '（未设置）',
        ));
    }

    private static function pemBody(string $pem): string
    {
        return (string)preg_replace('/-----BEGIN [^-]+-----|-----END [^-]+-----|\s+/', '', $pem);
    }
}
