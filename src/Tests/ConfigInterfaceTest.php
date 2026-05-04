<?php

declare(strict_types=1);

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

#[CoversClass(ConfigInterface::class)]
final class ConfigInterfaceTest extends TestCase
{
    public function testAllConfigClassesImplementContract(): void
    {
        foreach ([
            new WechatPlatformConfig('wx_app', 'secret'),
            new WechatWxappConfig('wx_mini', 'secret'),
            new WechatServiceConfig('wx_component', 'secret', 'token', 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG'),
            new WechatPaymentConfig('wx_app', 'mch', str_repeat('k', 32), 'serial', 'private-key'),
            new AlipayPlatformConfig('ali_app', 'private-key'),
            new AlipayPaymentConfig('ali_pay', 'private-key'),
        ] as $config) {
            $this->assertInstanceOf(ConfigInterface::class, $config);
        }
    }

    public function testConfigValidateRequiredFieldsOnConstruct(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('不能为空');

        new WechatPlatformConfig('', 'secret');
    }

    public function testConfigValidateRequiredFieldsFromArray(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('不能为空');

        WechatPaymentConfig::fromArray(['appid' => 'wx_app']);
    }

    public function testAlipayPaymentFromArrayReturnsChildClass(): void
    {
        $config = AlipayPaymentConfig::fromArray([
            'appid' => 'ali_pay',
            'private_key' => 'private-key',
        ]);

        $this->assertInstanceOf(AlipayPaymentConfig::class, $config);
    }
}
