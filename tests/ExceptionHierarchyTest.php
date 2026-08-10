<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\TestCase;
use We\Exception\AlipayApiException;
use We\Exception\AlipayException;
use We\Exception\AlipaySignatureException;
use We\Exception\ApiException;
use We\Exception\SdkException;
use We\Exception\SignatureException;
use We\Exception\TransportException;
use We\Exception\WechatException;

/**
 * @internal
 * @coversNothing
 */
final class ExceptionHierarchyTest extends TestCase
{
    public function testAllPlatformFailuresCanBeCaughtAsSdkFailures(): void
    {
        self::assertInstanceOf(SdkException::class, new WechatException('wechat'));
        self::assertInstanceOf(SdkException::class, new AlipayException('alipay'));
        self::assertInstanceOf(WechatException::class, new ApiException('wechat api'));
        self::assertInstanceOf(WechatException::class, new SignatureException('wechat signature'));
        self::assertInstanceOf(AlipayException::class, new AlipayApiException('alipay api'));
        self::assertInstanceOf(AlipayException::class, new AlipaySignatureException('alipay signature'));
        self::assertInstanceOf(SdkException::class, new TransportException('transport'));
    }

    public function testSdkFailureExposesDiagnosticContext(): void
    {
        $exception = new SdkException('failure', 7, null, ['platform_code' => 'INVALID']);

        self::assertSame(7, $exception->getCode());
        self::assertSame(['platform_code' => 'INVALID'], $exception->context());
    }
}
