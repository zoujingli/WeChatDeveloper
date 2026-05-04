<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Support\MessageCrypto;
use We\Support\Signature;

#[CoversClass(MessageCrypto::class)]
final class MessageCryptoTest extends TestCase
{
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
}
