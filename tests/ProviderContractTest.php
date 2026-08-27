<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\TestCase;
use We\Alipay\Common\StaticTokenProvider;
use We\Alipay\Common\TokenKind;
use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Exception\StreamException;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Common\Runtime;
use We\Wechat\Common\Internal\CacheKey;
use We\Wechat\Common\Internal\TokenCacheKey;
use We\Wechat\Common\Internal\TokenHttpClient;
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
        $manager = $this->tokenManager($cache, $transport);
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
            new TokenHttpClient($transport, (new Runtime(transport: $transport))->spooler()),
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

    public function testTokenHttpPipelineRejectsHttpFailureWithoutCaching(): void
    {
        $cache = new MemoryCache();
        $transport = new RecordingTransport([
            new PsrResponse(500, ['Request-Id' => 'token-http-500'], '{"access_token":"INVALID","expires_in":7200}'),
        ]);

        try {
            $this->tokenManager($cache, $transport)->token(
                'wechat.platform',
                'http-error',
                'appid',
                'secret',
                WeChatTokenStrategy::Standard,
                new Endpoint('https://api.weixin.qq.com'),
            );
            self::fail('预期 Token HTTP 错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame('wechat.platform', $exception->channel());
            self::assertSame('token-http-500', $exception->requestId());
            self::assertSame(500, $exception->platformCode());
        }
        self::assertSame([], $cache->values);
        self::assertSame([20_000], $transport->timeouts);
    }

    public function testTokenHttpPipelineRejectsInvalidJsonWithoutCaching(): void
    {
        $cache = new MemoryCache();
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Request-Id' => 'token-invalid-json'], '{invalid'),
        ]);

        try {
            $this->tokenManager($cache, $transport)->token(
                'wechat.wxapp',
                'invalid-json',
                'appid',
                'secret',
                WeChatTokenStrategy::Standard,
                new Endpoint('https://api.weixin.qq.com'),
            );
            self::fail('预期 Token 非法 JSON 被拒绝');
        } catch (ProtocolException $exception) {
            self::assertSame('wechat.wxapp', $exception->channel());
            self::assertSame('token-invalid-json', $exception->requestId());
        }
        self::assertSame([], $cache->values);
    }

    public function testTokenHttpPipelineRejectsPlatformErrorWithoutCaching(): void
    {
        $cache = new MemoryCache();
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Request-Id' => 'token-platform-error'], '{"errcode":40013,"errmsg":"invalid appid"}'),
        ]);

        try {
            $this->tokenManager($cache, $transport)->token(
                'wechat.platform',
                'platform-error',
                'appid',
                'secret',
                WeChatTokenStrategy::Stable,
                new Endpoint('https://api.weixin.qq.com'),
            );
            self::fail('预期 Token 平台错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame(40013, $exception->platformCode());
            self::assertSame('token-platform-error', $exception->requestId());
        }
        self::assertSame([], $cache->values);
    }

    public function testTokenHttpPipelineLimitsResponseBytes(): void
    {
        $cache = new MemoryCache();
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Request-Id' => 'token-too-large'], str_repeat('x', 1_048_577)),
        ]);

        try {
            $this->tokenManager($cache, $transport)->token(
                'wechat.platform',
                'oversized',
                'appid',
                'secret',
                WeChatTokenStrategy::Standard,
                new Endpoint('https://api.weixin.qq.com'),
            );
            self::fail('预期过大的 Token 响应被拒绝');
        } catch (StreamException $exception) {
            self::assertSame('wechat.platform', $exception->channel());
            self::assertSame('token-too-large', $exception->requestId());
        }
        self::assertSame([], $cache->values);
    }

    private function tokenManager(MemoryCache $cache, RecordingTransport $transport): WeChatTokenManager
    {
        $runtime = new Runtime(transport: $transport);

        return new WeChatTokenManager(
            $cache,
            new TokenHttpClient($transport, $runtime->spooler()),
            'test',
        );
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
