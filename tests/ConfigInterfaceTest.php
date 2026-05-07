<?php

declare(strict_types=1);

/**
 * 平台配置契约与配置对象测试。
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
            new WechatServiceConfig('wx_component', 'secret', 'token', 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG'),
            new WechatPaymentConfig('wx_app', 'mch', str_repeat('k', 32), 'serial', 'private-key'),
            new AlipayPlatformConfig('ali_app', 'private-key'),
            new AlipayPaymentConfig('ali_pay', 'private-key'),
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
     * 测试支付宝支付配置 fromArray 返回子类实例。
     */
    public function testAlipayPaymentFromArrayReturnsChildClass(): void
    {
        $config = AlipayPaymentConfig::fromArray([
            'appid' => 'ali_pay',
            'private_key' => 'private-key',
        ]);

        $this->assertInstanceOf(AlipayPaymentConfig::class, $config);
    }
}
