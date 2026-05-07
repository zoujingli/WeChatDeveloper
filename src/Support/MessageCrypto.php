<?php

declare(strict_types=1);

/**
 * 微信消息安全模式加解密工具。
 */

namespace We\Support;

use We\Exception\SignatureException;
use We\Exception\WechatException;

/**
 * 微信消息安全模式加解密工具。
 *
 * 实现微信服务器推送消息安全模式下的 AES-CBC 解密、加密和 msg_signature 校验。
 */
final class MessageCrypto
{
    private string $aesKey;

    /**
     * 创建微信消息加解密工具并解析 EncodingAESKey。
     */
    public function __construct(
        private string $token,
        string $encodingAesKey,
        private string $appid,
    ) {
        if (strlen($encodingAesKey) !== 43) {
            throw new WechatException('EncodingAESKey 必须是 43 位有效字符串');
        }
        $key = base64_decode($encodingAesKey . '=', true);
        if ($key === false || strlen($key) !== 32) {
            throw new WechatException('EncodingAESKey 必须是 43 位有效字符串');
        }
        $this->aesKey = $key;
    }

    /**
     * 校验 msg_signature 并解密微信安全模式 XML 消息。
     *
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

        $cipher = base64_decode($encrypt, true);
        if ($cipher === false || $cipher === '') {
            throw new WechatException('微信加密消息密文 Base64 无效');
        }
        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($this->aesKey, 0, 16));
        if (!is_string($plain) || $plain === '') {
            throw new WechatException('微信加密消息解密失败');
        }

        $plain = $this->removePadding($plain);
        if (strlen($plain) < 20) {
            throw new WechatException('微信加密消息明文长度无效');
        }
        $length = unpack('N', substr($plain, 16, 4))[1] ?? 0;
        if (!is_int($length) || strlen($plain) < 20 + $length) {
            throw new WechatException('微信加密消息内容长度无效');
        }
        $message = substr($plain, 20, (int)$length);
        $appid = substr($plain, 20 + (int)$length);
        if ($message === '' || $appid === '') {
            throw new WechatException('微信加密消息内容无效');
        }
        if (!hash_equals($this->appid, $appid)) {
            throw new SignatureException('微信加密消息 AppID 不匹配');
        }

        return Xml::decode($message);
    }

    /**
     * 加密回复 XML，并生成包含 Encrypt、MsgSignature、TimeStamp、Nonce 的安全模式响应 XML。
     */
    public function encryptMessage(string $xml, string $timestamp, string $nonce): string
    {
        $random = random_bytes(16);
        $payload = $random . pack('N', strlen($xml)) . $xml . $this->appid;
        $payload .= str_repeat(chr($pad = 32 - strlen($payload) % 32), $pad);
        $cipher = openssl_encrypt($payload, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($this->aesKey, 0, 16));
        if (!is_string($cipher) || $cipher === '') {
            throw new WechatException('微信加密消息加密失败');
        }
        $encrypted = base64_encode($cipher);
        $signature = Signature::sha1([$this->token, $timestamp, $nonce, $encrypted]);

        return Xml::encode([
            'Encrypt' => $encrypted,
            'MsgSignature' => $signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce,
        ]);
    }

    /**
     * 移除微信消息加解密协议中的 PKCS#7 填充。
     */
    private function removePadding(string $data): string
    {
        if ($data === '') {
            throw new WechatException('微信加密消息填充无效');
        }
        $pad = ord(substr($data, -1));
        if ($pad < 1 || $pad > 32) {
            throw new WechatException('微信加密消息填充无效');
        }
        if (strlen($data) < $pad || substr($data, -$pad) !== str_repeat(chr($pad), $pad)) {
            throw new WechatException('微信加密消息填充无效');
        }

        return substr($data, 0, strlen($data) - $pad);
    }
}
