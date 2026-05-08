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
use We\Support\PaymentCrypto;

/**
 * 微信支付 APIv3 通知 resource 解密测试用例。
 * @internal
 */
#[CoversClass(PaymentCrypto::class)]
final class PaymentCryptoTest extends TestCase
{
    /**
     * 测试微信支付回调资源解密。
     */
    public function testDecryptResource(): void
    {
        $key = str_repeat('k', 32);
        $nonce = '123456789012';
        $aad = 'transaction';
        $plain = '{"out_trade_no":"T202605010001","trade_state":"SUCCESS"}';
        $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);

        $data = PaymentCrypto::decryptResource($key, [
            'ciphertext' => base64_encode($cipher . $tag),
            'nonce' => $nonce,
            'associated_data' => $aad,
        ]);

        $this->assertSame('T202605010001', $data['out_trade_no']);
        $this->assertSame('SUCCESS', $data['trade_state']);
    }
}
