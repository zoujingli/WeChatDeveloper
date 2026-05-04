<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Client;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Exception\WechatException;
use We\Platform\Alipay\PlatformClient as AlipayPlatformClient;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Platform\Wechat\ServiceClient as WechatServiceClient;

#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function testConstructorThrowsWhenCacheKeyPrefixEmpty(): void
    {
        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('cacheKeyPrefix');
        new Client(cacheKeyPrefix: '   ');
    }

    public function testDefaultCacheStoreDirectoryUnderSysTemp(): void
    {
        $dir = Client::defaultCacheStoreDirectory();
        $this->assertStringStartsWith(rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/'), $dir);
        $this->assertStringEndsWith(Client::DEFAULT_CACHE_STORE_DIR_NAME, $dir);
    }

    public function testGetThrowsWhenChannelUnsupported(): void
    {
        $client = new Client();

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('不支持的通道标识');
        $client->get('unknown.channel', new WechatPlatformConfig('wx_x', 'sec'));
    }

    public function testGetThrowsWhenConfigMismatch(): void
    {
        $client = new Client();

        $this->expectException(WechatException::class);
        $this->expectExceptionMessage('WechatPlatformConfig');
        $client->get('wechat.platform', new WechatServiceConfig('app', 'sec', 'token', 'encoding'));
    }

    public function testWechatPlatformFactoryReturnsTypedClient(): void
    {
        $client = new Client();
        $wechat = $client->wechatPlatform(new WechatPlatformConfig('wx_appid', 'app_secret'));

        $this->assertInstanceOf(WechatPlatformClient::class, $wechat);
    }

    public function testServiceClientAuthorizationUrlStillAvailable(): void
    {
        $client = new Client();
        $service = $client->wechatService(new WechatServiceConfig(
            'wx_component',
            'component_secret',
            'component_token',
            'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG'
        ));
        $result = $service->authorizationUrl('preauthcode', 'https://example.com/callback', 3, 'STATE_TEST');

        $this->assertStringContainsString('componentloginpage', $result);
        $this->assertStringContainsString('pre_auth_code=preauthcode', $result);
    }

    public function testAlipayPlatformCallReturnsAuthorizationUrl(): void
    {
        $client = new Client();
        $alipay = $client->alipayPlatform(new AlipayPlatformConfig('202605010001', str_repeat('a', 64)));
        $result = $alipay->get('auth', [
            'redirect_uri' => 'https://example.com/alipay/callback',
            'scope' => 'auth_user',
            'state' => 'S2',
        ]);

        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('state=S2', (string)$result['url']);
    }

    public function testGetCanReturnSpecificChannelClient(): void
    {
        $client = new Client();
        $channelClient = $client->get('alipay.platform', new AlipayPlatformConfig('202605010001', str_repeat('a', 64)));

        $this->assertInstanceOf(AlipayPlatformClient::class, $channelClient);
        $this->assertNotInstanceOf(WechatServiceClient::class, $channelClient);
    }
}
