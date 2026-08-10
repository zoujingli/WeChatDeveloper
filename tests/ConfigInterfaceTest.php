<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use We\Config\AlipayPaymentConfig;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Config\WechatWxappConfig;
use We\Contract\ConfigInterface;
use We\Exception\AlipayException;
use We\Exception\WechatException;

/**
 * 平台配置契约与配置对象测试用例。
 * @internal
 */
#[CoversClass(ConfigInterface::class)]
final class ConfigInterfaceTest extends TestCase
{
    /**
     * 测试所有配置对象都实现配置契约。
     */
    public function testAllConfigClassesImplementContract(): void
    {
        foreach ([
            new WechatPlatformConfig('wx_app', 'secret'),
            new WechatWxappConfig('wx_wxapp', 'secret'),
            new WechatServiceConfig('wx_component', 'secret', 'token', TestKeys::encodingAesKey()),
            new WechatPaymentConfig(
                'wx_app',
                'mch',
                str_repeat('k', 32),
                'serial',
                TestKeys::privateKey(),
                platformPublicKey: TestKeys::publicKey(),
                platformSerial: 'platform-serial',
            ),
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey(), TestKeys::publicKey()),
            new AlipayPaymentConfig('ali_pay', TestKeys::privateKey(), TestKeys::publicKey()),
        ] as $config) {
            $this->assertInstanceOf(ConfigInterface::class, $config);
        }
    }

    /**
     * 测试构造配置对象时会校验必填字段。
     */
    public function testConfigValidateRequiredFieldsOnConstruct(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('不能为空');

        new WechatPlatformConfig('', 'secret');
    }

    /**
     * 测试通过数组构造配置对象时会校验必填字段。
     */
    public function testConfigValidateRequiredFieldsFromArray(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('不能为空');

        WechatPaymentConfig::fromArray(['appid' => 'wx_app']);
    }

    /**
     * 测试微信服务平台配置会校验 EncodingAESKey 格式。
     */
    public function testWechatServiceConfigRejectsInvalidEncodingAesKey(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('componentEncodingAesKey');

        new WechatServiceConfig('wx_component', 'secret', 'token', 'invalid');
    }

    /**
     * 测试微信消息 Token 只能使用官方允许的英文或数字格式。
     */
    public function testWechatConfigRejectsInvalidMessageToken(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('token');

        new WechatPlatformConfig('wx_app', 'secret', 'message_token', TestKeys::encodingAesKey());
    }

    /**
     * 测试微信支付配置会校验 APIv3 Key 与商户私钥格式。
     */
    public function testWechatPaymentConfigRejectsInvalidCryptoMaterial(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('merchantPrivateKey');

        new WechatPaymentConfig('wx_app', 'mch', str_repeat('k', 32), 'serial', 'invalid-private-key');
    }

    /**
     * 测试支付宝支付配置 fromArray 返回子类实例。
     */
    public function testAlipayPaymentFromArrayReturnsChildClass(): void
    {
        $config = AlipayPaymentConfig::fromArray([
            'appid' => 'ali_pay',
            'private_key' => TestKeys::privateKey(),
            'alipay_public_key' => TestKeys::publicKey(),
        ]);

        $this->assertInstanceOf(AlipayPaymentConfig::class, $config);
    }

    public function testPaymentConfigsRejectNonRsaKeys(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('RSA');

        new WechatPaymentConfig(
            'wx_app',
            'mch',
            str_repeat('k', 32),
            'merchant-serial',
            TestKeys::ecPrivateKey(),
            platformPublicKey: TestKeys::publicKey(),
            platformSerial: 'platform-serial',
        );
    }

    public function testAlipayConfigRequiresPlatformPublicKey(): void
    {
        $this->expectException(AlipayException::class);
        $this->expectExceptionMessage('alipay_public_key');

        new AlipayPlatformConfig('ali_app', TestKeys::privateKey());
    }

    public function testWechatPaymentConfigRequiresPlatformVerificationMaterial(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('platform');

        new WechatPaymentConfig('wx_app', 'mch', str_repeat('k', 32), 'merchant-serial', TestKeys::privateKey());
    }

    public function testAlipayAcceptsHeaderlessPkcs8AndPkcs1PrivateKeys(): void
    {
        $pkcs8 = new AlipayPlatformConfig(
            'ali_pkcs8',
            TestKeys::privateKeyBody(),
            TestKeys::publicKeyBody(),
        );
        $pkcs1 = new AlipayPlatformConfig(
            'ali_pkcs1',
            TestKeys::pkcs1PrivateKeyBody(),
            TestKeys::publicKeyBody(),
        );

        self::assertSame('ali_pkcs8', $pkcs8->appid);
        self::assertSame('ali_pkcs1', $pkcs1->appid);
    }

    public function testLegacyMerchantCertificateIsNotMappedToPlatformCertificate(): void
    {
        $config = WechatPaymentConfig::fromArray([
            'appid' => 'wx_app',
            'mch_id' => 'mch',
            'api_v3_key' => str_repeat('k', 32),
            'merchant_serial' => 'merchant-serial',
            'merchant_private_key' => TestKeys::privateKey(),
            'cert_public' => TestKeys::publicKey(),
            'platform_public_key' => TestKeys::publicKey(),
            'platform_serial' => 'platform-serial',
        ]);

        self::assertSame('', $config->platformCertificate);
        self::assertSame(TestKeys::publicKey(), $config->platformPublicKey);
    }

    #[DataProvider('invalidNotificationToleranceValues')]
    public function testWechatPaymentFromArrayRejectsImplicitlyDisabledFreshness(mixed $value): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('notification_tolerance_seconds');

        WechatPaymentConfig::fromArray(self::wechatPaymentData([
            'notification_tolerance_seconds' => $value,
        ]));
    }

    /**
     * @return iterable<string,array{mixed}>
     */
    public static function invalidNotificationToleranceValues(): iterable
    {
        yield 'empty string' => [''];
        yield 'boolean false' => [false];
        yield 'word' => ['disabled'];
        yield 'float' => [1.5];
    }

    public function testWechatPaymentFromArrayAcceptsExplicitNumericZeroFreshness(): void
    {
        $config = WechatPaymentConfig::fromArray(self::wechatPaymentData([
            'notification_tolerance_seconds' => '0',
        ]));

        self::assertSame(0, $config->notificationToleranceSeconds);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private static function wechatPaymentData(array $overrides): array
    {
        return array_merge([
            'appid' => 'wx_app',
            'mch_id' => 'mch',
            'api_v3_key' => str_repeat('k', 32),
            'merchant_serial' => 'merchant-serial',
            'merchant_private_key' => TestKeys::privateKey(),
            'platform_public_key' => TestKeys::platformKeyPair()[1],
            'platform_serial' => 'platform-serial',
        ], $overrides);
    }
}
