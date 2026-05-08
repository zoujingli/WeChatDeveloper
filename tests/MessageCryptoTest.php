<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Exception\SignatureException;
use We\Exception\WechatException;
use We\Support\MessageCrypto;
use We\Support\Signature;

/**
 * 微信消息安全模式加解密测试用例。
 * @internal
 */
#[CoversClass(MessageCrypto::class)]
final class MessageCryptoTest extends TestCase
{
    /**
     * 测试微信消息加密后可以正确解密。
     */
    public function testEncryptAndDecryptMessage(): void
    {
        $key = substr(base64_encode(str_repeat('a', 32)), 0, 43);
        $crypto = new MessageCrypto('token', $key, 'wx1234567890');
        $xml = '<xml><ToUserName><![CDATA[to]]></ToUserName><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[hello]]></Content></xml>';
        $timestamp = '1777600000';
        $nonce = 'nonce';

        $encrypted = $crypto->encryptMessage($xml, $timestamp, $nonce);
        preg_match('/<Encrypt><!\[CDATA\[(.*?)\]\]><\/Encrypt>/', $encrypted, $matches);
        $this->assertNotEmpty($matches[1] ?? '');
        $signature = Signature::sha1(['token', $timestamp, $nonce, $matches[1]]);

        $plain = $crypto->decryptMessage($encrypted, $signature, $timestamp, $nonce);

        $this->assertSame('text', $plain['MsgType']);
        $this->assertSame('hello', $plain['Content']);
    }

    /**
     * 测试微信消息密文 Base64 无效时抛出异常。
     */
    public function testDecryptRejectsInvalidBase64Cipher(): void
    {
        $crypto = new MessageCrypto('token', self::encodingAesKey(), 'wx1234567890');
        $timestamp = '1777600000';
        $nonce = 'nonce';
        $encrypt = 'not@@base64';
        $xml = '<xml><Encrypt><![CDATA[' . $encrypt . ']]></Encrypt></xml>';
        $signature = Signature::sha1(['token', $timestamp, $nonce, $encrypt]);

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('Base64');

        $crypto->decryptMessage($xml, $signature, $timestamp, $nonce);
    }

    /**
     * 测试微信消息填充无效时抛出异常。
     */
    public function testDecryptRejectsInvalidPadding(): void
    {
        $key = self::encodingAesKey();
        $crypto = new MessageCrypto('token', $key, 'wx1234567890');
        $xml = '<xml><MsgType><![CDATA[text]]></MsgType></xml>';
        $timestamp = '1777600000';
        $nonce = 'nonce';
        $encrypt = self::encryptRawWithInvalidPadding($key, $xml, 'wx1234567890');
        $signature = Signature::sha1(['token', $timestamp, $nonce, $encrypt]);

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('填充');

        $crypto->decryptMessage('<xml><Encrypt><![CDATA[' . $encrypt . ']]></Encrypt></xml>', $signature, $timestamp, $nonce);
    }

    /**
     * 测试微信消息 AppID 不匹配时抛出签名异常。
     */
    public function testDecryptRejectsAppidMismatch(): void
    {
        $key = self::encodingAesKey();
        $encryptor = new MessageCrypto('token', $key, 'wx_source');
        $decryptor = new MessageCrypto('token', $key, 'wx_other');
        $timestamp = '1777600000';
        $nonce = 'nonce';
        $encrypted = $encryptor->encryptMessage('<xml><MsgType><![CDATA[text]]></MsgType></xml>', $timestamp, $nonce);
        preg_match('/<Encrypt><!\[CDATA\[(.*?)\]\]><\/Encrypt>/', $encrypted, $matches);
        $signature = Signature::sha1(['token', $timestamp, $nonce, $matches[1] ?? '']);

        $this->expectException(SignatureException::class);
        $this->expectExceptionMessage('AppID');

        $decryptor->decryptMessage($encrypted, $signature, $timestamp, $nonce);
    }

    /**
     * 生成测试使用的 EncodingAESKey。
     */
    private static function encodingAesKey(): string
    {
        return substr(base64_encode(str_repeat('a', 32)), 0, 43);
    }

    /**
     * 构造填充无效的加密消息密文。
     */
    private static function encryptRawWithInvalidPadding(string $encodingAesKey, string $xml, string $appid): string
    {
        $aesKey = base64_decode($encodingAesKey . '=', true);
        self::assertIsString($aesKey);
        $payload = str_repeat('r', 16) . pack('N', strlen($xml)) . $xml . $appid;
        $pad = 32 - strlen($payload) % 32;
        $payload .= str_repeat("\0", $pad);
        $cipher = openssl_encrypt($payload, 'AES-256-CBC', $aesKey, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, substr($aesKey, 0, 16));
        self::assertIsString($cipher);

        return base64_encode($cipher);
    }
}
