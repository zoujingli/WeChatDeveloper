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
use We\Config\AlipayPaymentConfig;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Config\WechatWxappConfig;
use We\Contract\ConfigInterface;
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
            new WechatPaymentConfig('wx_app', 'mch', str_repeat('k', 32), 'serial', TestKeys::privateKey()),
            new AlipayPlatformConfig('ali_app', TestKeys::privateKey()),
            new AlipayPaymentConfig('ali_pay', TestKeys::privateKey()),
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
        ]);

        $this->assertInstanceOf(AlipayPaymentConfig::class, $config);
    }
}
