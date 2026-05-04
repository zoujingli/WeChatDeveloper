<?php

declare(strict_types=1);

namespace We\Support;

use We\Exception\SignatureException;
use We\Exception\WechatException;

final class MessageCrypto
{
    private string $aesKey;

    public function __construct(
        private string $token,
        string $encodingAesKey,
        private string $appid,
    ) {
        $key = base64_decode($encodingAesKey . '=', true);
        if ($key === false || strlen($key) !== 32) {
            throw new WechatException('EncodingAESKey 必须是 43 位有效字符串');
        }
        $this->aesKey = $key;
    }

    /**
     * @return array<string,mixed>
     */
    public function decryptMessage(string $xml, string $msgSignature, string $timestamp, string $nonce): array
    {
        $payload = Xml::decode($xml);
        $encrypt = (string)($payload['Encrypt'] ?? '');
        if ($encrypt === '') {
            throw new WechatException('微信加密消息缺少 Encrypt 字段');
        }

        Signature::assertSha1($msgSignature, [$this->token, $timestamp, $nonce, $encrypt]);

        $plain = openssl_decrypt(base64_decode($encrypt, true) ?: '', 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($this->aesKey, 0, 16));
        if (!is_string($plain) || $plain === '') {
            throw new WechatException('微信加密消息解密失败');
        }

        $plain = $this->removePadding($plain);
        $length = unpack('N', substr($plain, 16, 4))[1] ?? 0;
        $message = substr($plain, 20, (int)$length);
        $appid = substr($plain, 20 + (int)$length);
        if (!hash_equals($this->appid, $appid)) {
            throw new SignatureException('微信加密消息 AppID 不匹配');
        }

        return Xml::decode($message);
    }

    public function encryptMessage(string $xml, string $timestamp, string $nonce): string
    {
        $random = random_bytes(16);
        $payload = $random . pack('N', strlen($xml)) . $xml . $this->appid;
        $payload .= str_repeat(chr($pad = 32 - strlen($payload) % 32), $pad);
        $encrypted = base64_encode(openssl_encrypt($payload, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($this->aesKey, 0, 16)) ?: '');
        $signature = Signature::sha1([$this->token, $timestamp, $nonce, $encrypted]);

        return Xml::encode([
            'Encrypt' => $encrypted,
            'MsgSignature' => $signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce,
        ]);
    }

    private function removePadding(string $data): string
    {
        $pad = ord(substr($data, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }

        return substr($data, 0, strlen($data) - $pad);
    }
}
