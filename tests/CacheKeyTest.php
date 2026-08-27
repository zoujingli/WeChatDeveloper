<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Common\Exception\SdkException;
use We\Wechat\Common\Internal\CacheKey;
use We\Wechat\Common\Internal\TokenCacheKey;

/**
 * SDK 缓存键生成规则测试。
 *
 * @internal
 */
#[CoversClass(CacheKey::class)]
final class CacheKeyTest extends TestCase
{
    public function testComposeThreeSegments(): void
    {
        $logical = TokenCacheKey::weChatAccessToken('wx_demo', '');
        $full = CacheKey::compose('myapp', 'wechat.platform', $logical);
        $this->assertSame('myapp.wechat%2Eplatform.' . rawurlencode($logical), $full);
        $this->assertDoesNotMatchRegularExpression('/[{}()\/\\\@:]/', $full);
    }

    public function testComposeTrimsColonNoiseOnSegments(): void
    {
        $logical = TokenCacheKey::wechatOpenComponentAccessToken('wx_service');
        $this->assertSame(
            'ns.wechat%2Eservice.' . rawurlencode($logical),
            CacheKey::compose('::ns::', ':::wechat.service::', $logical),
        );
    }

    public function testWechatTokenLogicalKeysFollowChannelNames(): void
    {
        $this->assertSame(
            'wechat:app:wx_platform:platform:access_token',
            TokenCacheKey::weChatAccessToken('wx_platform'),
        );
        $this->assertSame(
            'wechat:app:wx_wxapp:wxapp:access_token',
            TokenCacheKey::wxAppAccessToken('wx_wxapp'),
        );
        $this->assertSame(
            'wechat:app:wx_service:service:component_access_token',
            TokenCacheKey::wechatOpenComponentAccessToken('wx_service'),
        );
        $this->assertSame(
            'wechat:app:wx_service:service:authorizer:wx_authorizer:access_token',
            TokenCacheKey::wechatOpenAuthorizerAccessToken('wx_service', 'wx_authorizer'),
        );
    }

    public function testComposeThrowsWhenPrefixEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('通用前缀');
        CacheKey::compose('', 'wechat.wxapp', 'wechat:app:x:wxapp:access_token');
    }

    public function testComposeThrowsWhenChannelEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('通道段');
        CacheKey::compose('app', '', 'wechat:app:x:wxapp:access_token');
    }

    public function testComposeThrowsWhenLogicalEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('逻辑段');
        CacheKey::compose('app', 'wechat.platform', '  ');
    }

    public function testComposeEncodesDelimiterDotsWithoutCrossSegmentCollisions(): void
    {
        $left = CacheKey::compose('a', 'wechat.platform', 'b.wechat.platform.c');
        $right = CacheKey::compose('a.wechat.platform.b', 'wechat.platform', 'c');

        self::assertNotSame($left, $right);
        self::assertSame('a.wechat%2Eplatform.b%2Ewechat%2Eplatform%2Ec', $left);
        self::assertSame('a%2Ewechat%2Eplatform%2Eb.wechat%2Eplatform.c', $right);
    }
}
