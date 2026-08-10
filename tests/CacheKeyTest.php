<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Exception\SdkException;
use We\Support\CacheKey;
use We\Support\TokenCacheKey;

/**
 * SDK 缓存键生成规则测试用例。
 * @internal
 */
#[CoversClass(CacheKey::class)]
final class CacheKeyTest extends TestCase
{
    /**
     * 测试缓存完整键由三段组成。
     */
    public function testComposeThreeSegments(): void
    {
        $logical = TokenCacheKey::wechatPlatformAccessToken('wx_demo', '');
        $full = CacheKey::compose('myapp', 'wechat.platform', $logical);
        $this->assertSame('myapp.wechat.platform.' . rawurlencode($logical), $full);
        $this->assertDoesNotMatchRegularExpression('/[{}()\/\\\@:]/', $full);
    }

    /**
     * 测试缓存键段会去除多余冒号。
     */
    public function testComposeTrimsColonNoiseOnSegments(): void
    {
        $logical = TokenCacheKey::wechatServiceComponentAccessToken('wx_service');
        $this->assertSame(
            'ns.wechat.service.' . rawurlencode($logical),
            CacheKey::compose('::ns::', ':::wechat.service::', $logical),
        );
    }

    /**
     * 测试微信 Token 逻辑键使用 platform、wxapp、service 统一命名。
     */
    public function testWechatTokenLogicalKeysFollowChannelNames(): void
    {
        $this->assertSame(
            'wechat:app:wx_platform:platform:access_token',
            TokenCacheKey::wechatPlatformAccessToken('wx_platform'),
        );
        $this->assertSame(
            'wechat:app:wx_wxapp:wxapp:access_token',
            TokenCacheKey::wechatWxappAccessToken('wx_wxapp'),
        );
        $this->assertSame(
            'wechat:app:wx_service:service:component_access_token',
            TokenCacheKey::wechatServiceComponentAccessToken('wx_service'),
        );
        $this->assertSame(
            'wechat:app:wx_service:service:authorizer:wx_authorizer:access_token',
            TokenCacheKey::wechatServiceAuthorizerAccessToken('wx_service', 'wx_authorizer'),
        );
    }

    /**
     * 测试缓存键前缀为空时抛出异常。
     */
    public function testComposeThrowsWhenPrefixEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('通用前缀');
        CacheKey::compose('', 'wechat.wxapp', 'wechat:app:x:wxapp:access_token');
    }

    /**
     * 测试缓存键通道段为空时抛出异常。
     */
    public function testComposeThrowsWhenChannelEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('通道段');
        CacheKey::compose('app', '', 'wechat:app:x:wxapp:access_token');
    }

    /**
     * 测试缓存键逻辑段为空时抛出异常。
     */
    public function testComposeThrowsWhenLogicalEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('逻辑段');
        CacheKey::compose('app', 'wechat.platform', '  ');
    }
}
