<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use We\Alipay\Common\StaticTokenProvider;
use We\Alipay\Common\TokenKind;
use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\ProtocolException;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Wechat\Common\Internal\CacheKey;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\Common\Internal\WeChatTokenManager;
use We\Wechat\Common\WeChatTokenStrategy;
use We\Wechat\WxOpen\Internal\WxOpenTokenManager;
use We\Wechat\WxOpen\StaticComponentTicketProvider;
use We\Wechat\WxOpen\StoreTokenInterface;
use We\Wechat\WxOpenConfig;

/**
 * Provider 与微信 Token 生命周期契约测试。
 *
 * @internal
 * @coversNothing
 */
final class ProviderContractTest extends TestCase
{
    public function testPemSignerAndStaticTrustAreMinimalAdapters(): void
    {
        $signer = new PemSigningKeyProvider('key-1', TestKeys::privateKey());
        $signature = base64_decode($signer->sign('message'), true);
        $public = openssl_pkey_get_public(TestKeys::publicKey());
        self::assertIsString($signature);
        self::assertNotFalse($public);
        self::assertSame(1, openssl_verify('message', $signature, $public, OPENSSL_ALGO_SHA256));

        $trust = new StaticTrustMaterialProvider(['channel' => ['known' => TestKeys::publicKey()]]);
        self::assertSame(trim(TestKeys::publicKey()), $trust->publicKey('channel', 'known'));
        $this->expectException(ConfigurationException::class);
        $trust->publicKey('channel', 'missing');
    }

    public function testTokenProviderKeepsIdentityDomainsSeparate(): void
    {
        $provider = new StaticTokenProvider([
            TokenKind::AlipayUser->value => ['id' => 'USER'],
            TokenKind::AlipayApp->value => ['id' => 'APP'],
        ]);

        self::assertSame('USER', $provider->token(TokenKind::AlipayUser, 'id'));
        self::assertSame('APP', $provider->token(TokenKind::AlipayApp, 'id'));
    }

    public function testWeChatTokenManagerCachesStandardAndStableTokens(): void
    {
        $cache = new MemoryCache();
        $transport = new RecordingTransport([
            new PsrResponse(200, [], '{"access_token":"STANDARD","expires_in":7200}'),
            new PsrResponse(200, [], '{"access_token":"STABLE","expires_in":7200}'),
        ]);
        $manager = new WeChatTokenManager($cache, $transport, 'test');
        $endpoint = new Endpoint('https://api.weixin.qq.com');

        $standard = $manager->token('wechat.platform', 'standard', 'appid', 'secret', WeChatTokenStrategy::Standard, $endpoint);
        self::assertSame('STANDARD', $standard);
        self::assertSame('STANDARD', $manager->token('wechat.platform', 'standard', 'appid', 'secret', WeChatTokenStrategy::Standard, $endpoint));
        $stable = $manager->token('wechat.platform', 'stable', 'appid', 'secret', WeChatTokenStrategy::Stable, $endpoint);

        self::assertSame('STABLE', $stable);
        self::assertCount(2, $transport->requests);
        self::assertSame('GET', $transport->requests[0]->getMethod());
        self::assertSame('POST', $transport->requests[1]->getMethod());
        self::assertSame(2, $cache->lockCalls);
    }

    public function testInvalidAuthorizerRefreshIsNotPersisted(): void
    {
        $cache = new MemoryCache();
        $store = new RecordingAuthorizerStore();
        $transport = new RecordingTransport([
            new PsrResponse(200, [], '{"expires_in":7200}'),
        ]);
        $config = new WxOpenConfig('component_app', 'component_secret');
        $manager = new WxOpenTokenManager(
            $config,
            $cache,
            $transport,
            new StaticComponentTicketProvider(['component_app' => 'ticket']),
            $store,
            'test',
        );
        $cache->set(
            CacheKey::compose(
                'test',
                'wechat.service',
                TokenCacheKey::wechatOpenComponentAccessToken('component_app'),
            ),
            'component-token',
            3600,
        );

        try {
            $manager->authorizerToken('authorizer_app');
            self::fail('预期无效授权方刷新响应被拒绝');
        } catch (ProtocolException $exception) {
            self::assertStringContainsString('authorizer_access_token', $exception->getMessage());
        }
        self::assertSame([], $store->saved);
    }
}

/**
 * 记录授权方 Token 刷新结果的测试存储。
 *
 * @internal
 */
final class RecordingAuthorizerStore implements StoreTokenInterface
{
    /** @var list<array{appid:string,payload:array<string,mixed>}> */
    public array $saved = [];

    public function refreshToken(string $authorizerAppid): string
    {
        return 'refresh-token';
    }

    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void
    {
        $this->saved[] = ['appid' => $authorizerAppid, 'payload' => $payload];
    }
}
