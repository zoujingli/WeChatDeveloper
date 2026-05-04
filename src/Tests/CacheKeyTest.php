<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Exception\WechatException;
use We\Support\CacheKey;
use We\Support\TokenCacheKey;

#[CoversClass(CacheKey::class)]
final class CacheKeyTest extends TestCase
{
    public function testComposeThreeSegments(): void
    {
        $logical = TokenCacheKey::wechatOfficialAccessToken('wx_demo', '');
        $full = CacheKey::compose('myapp', 'wechat.platform', $logical);
        $this->assertSame('myapp:wechat.platform:' . $logical, $full);
    }

    public function testComposeTrimsColonNoiseOnSegments(): void
    {
        $logical = 'wechat:app:z:official:access_token';
        $this->assertSame('ns:wechat.service:' . $logical, CacheKey::compose('::ns::', ':::wechat.service::', $logical));
    }

    public function testComposeThrowsWhenPrefixEmpty(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('通用前缀');
        CacheKey::compose('', 'wechat.wxapp', 'wechat:app:x:mini:access_token');
    }

    public function testComposeThrowsWhenChannelEmpty(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('通道段');
        CacheKey::compose('app', '', 'wechat:app:x:mini:access_token');
    }

    public function testComposeThrowsWhenLogicalEmpty(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('逻辑段');
        CacheKey::compose('app', 'wechat.platform', '  ');
    }
}
