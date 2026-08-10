<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Client;
use We\Config\AlipayPlatformConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Exception\SdkException;
use We\Platform\Alipay\PaymentClient as AlipayPaymentClient;
use We\Platform\Alipay\PlatformClient as AlipayPlatformClient;
use We\Platform\Wechat\PaymentClient as WechatPaymentClient;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Platform\Wechat\ServiceClient as WechatServiceClient;
use We\Platform\Wechat\WxappClient as WechatWxappClient;

/**
 * SDK 根入口通道工厂测试用例。
 * @internal
 */
#[CoversClass(Client::class)]
final class ClientTest extends TestCase
{
    public function testPlatformFactoriesExposeConcreteReturnTypes(): void
    {
        $factories = [
            'wechatPlatform' => WechatPlatformClient::class,
            'wechatWxapp' => WechatWxappClient::class,
            'wechatService' => WechatServiceClient::class,
            'wechatPayment' => WechatPaymentClient::class,
            'alipayPlatform' => AlipayPlatformClient::class,
            'alipayPayment' => AlipayPaymentClient::class,
        ];
        $client = new \ReflectionClass(Client::class);

        foreach ($factories as $method => $returnType) {
            self::assertTrue($client->hasMethod($method), $method . ' must be a declared method');
            self::assertSame($returnType, (string)$client->getMethod($method)->getReturnType());
        }
        self::assertFalse($client->hasMethod('__call'));
    }

    /**
     * 测试根客户端缓存前缀为空时抛出异常。
     */
    public function testConstructorThrowsWhenCacheKeyPrefixEmpty(): void
    {
        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('cacheKeyPrefix');
        new Client(cacheKeyPrefix: '   ');
    }

    /**
     * 测试默认缓存目录位于系统临时目录下。
     */
    public function testDefaultCacheStoreDirectoryUnderSysTemp(): void
    {
        $dir = Client::defaultCacheStoreDirectory();
        $this->assertStringStartsWith(rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/'), $dir);
        $this->assertStringEndsWith(Client::DEFAULT_CACHE_STORE_DIR_NAME, $dir);
    }

    /**
     * 测试不支持的通道标识会抛出异常。
     */
    public function testGetThrowsWhenChannelUnsupported(): void
    {
        $client = new Client();

        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('不支持的通道标识');
        $client->get('unknown.channel', new WechatPlatformConfig('wx_x', 'sec'));
    }

    /**
     * 测试通道配置类型不匹配会抛出异常。
     */
    public function testGetThrowsWhenConfigMismatch(): void
    {
        $client = new Client();

        $this->expectException(SdkException::class);
        $this->expectExceptionMessage('WechatPlatformConfig');
        $client->get('wechat.platform', new WechatServiceConfig('app', 'sec', 'token', TestKeys::encodingAesKey()));
    }

    /**
     * 测试微信公众平台工厂返回正确客户端。
     */
    public function testWechatPlatformFactoryReturnsTypedClient(): void
    {
        $client = new Client();
        $wechat = $client->wechatPlatform(new WechatPlatformConfig('wx_appid', 'app_secret'));

        $this->assertInstanceOf(WechatPlatformClient::class, $wechat);
    }

    /**
     * 测试微信公众平台可生成 open.weixin.qq.com 网页授权地址。
     */
    public function testWechatPlatformBuildsOpenAuthorizeUrl(): void
    {
        $client = new Client();
        $wechat = $client->wechatPlatform(new WechatPlatformConfig('wx_appid', 'app_secret'));
        $result = $wechat->get('connect/oauth2/authorize', [
            'redirect_uri' => 'https://example.com/wechat/callback',
            'scope' => 'snsapi_userinfo',
            'state' => 'S1',
        ]);

        $this->assertArrayHasKey('url', $result);
        $this->assertStringStartsWith('https://open.weixin.qq.com/connect/oauth2/authorize?', (string)$result['url']);
        $this->assertStringContainsString('appid=wx_appid', (string)$result['url']);
        $this->assertStringContainsString('scope=snsapi_userinfo', (string)$result['url']);
        $this->assertStringEndsWith('#wechat_redirect', (string)$result['url']);
    }

    /**
     * 测试微信服务平台工厂返回正确客户端，并保留授权地址生成能力。
     */
    public function testWechatServiceFactoryReturnsTypedClientAndBuildsAuthorizationUrl(): void
    {
        $client = new Client();
        $service = $client->wechatService(new WechatServiceConfig(
            'wx_component',
            'component_secret',
            'componentToken123',
            TestKeys::encodingAesKey()
        ));
        $url = $service->authorizationUrl('preauthcode', 'https://example.com/callback', 3, 'STATE_TEST');

        $this->assertInstanceOf(WechatServiceClient::class, $service);
        $this->assertStringContainsString('componentloginpage', $url);
        $this->assertStringContainsString('pre_auth_code=preauthcode', $url);
        $this->assertStringContainsString('state=STATE_TEST', $url);
    }

    /**
     * 测试支付宝授权调用返回跳转地址。
     */
    public function testAlipayPlatformCallReturnsAuthorizationUrl(): void
    {
        $client = new Client();
        $alipay = $client->alipayPlatform(new AlipayPlatformConfig(
            '202605010001',
            TestKeys::privateKey(),
            TestKeys::publicKey(),
        ));
        $result = $alipay->get('auth', [
            'redirect_uri' => 'https://example.com/alipay/callback',
            'scope' => 'auth_user',
            'state' => 'S2',
        ]);

        $this->assertArrayHasKey('url', $result);
        $this->assertStringContainsString('state=S2', (string)$result['url']);
    }

    /**
     * 测试按通道字符串创建指定客户端。
     */
    public function testGetCanReturnSpecificChannelClient(): void
    {
        $client = new Client();
        $channelClient = $client->get('alipay.platform', new AlipayPlatformConfig(
            '202605010001',
            TestKeys::privateKey(),
            TestKeys::publicKey(),
        ));

        $this->assertInstanceOf(AlipayPlatformClient::class, $channelClient);
        $this->assertNotInstanceOf(WechatServiceClient::class, $channelClient);
    }
}
