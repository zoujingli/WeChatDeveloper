<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Client;
use We\Config\WechatPlatformConfig;
use We\Contract\StoreCacheInterface;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Support\CacheKey;
use We\Support\TokenCacheKey;

/**
 * 微信公众平台 access_token 缓存行为测试用例。
 * @internal
 */
#[CoversClass(WechatPlatformClient::class)]
final class AccessTokenCacheTest extends TestCase
{
    /**
     * 测试命中 access_token 缓存时不会发起 HTTP 请求。
     */
    public function testAccessTokenCacheHitDoesNotRequestHttp(): void
    {
        $cache = new ArrayCacheStore();
        $key = CacheKey::compose(
            Client::DEFAULT_CACHE_KEY_PREFIX,
            'wechat.platform',
            TokenCacheKey::wechatPlatformAccessToken('wx_app'),
        );
        $cache->set($key, 'cached-token', 3600);
        $http = new FakeHttpClient(['access_token' => 'remote-token', 'expires_in' => 7200]);

        $token = (new Client(cache: $cache, http: $http))
            ->wechatPlatform(new WechatPlatformConfig('wx_app', 'secret'))
            ->accessToken();

        $this->assertSame('cached-token', $token);
        $this->assertSame(0, $http->requests);
        $this->assertSame(0, $cache->lockCalls);
    }

    /**
     * 测试刷新 access_token 时会使用锁并写入缓存。
     */
    public function testAccessTokenRefreshUsesLockAndWritesCache(): void
    {
        $cache = new ArrayCacheStore();
        $http = new FakeHttpClient(['access_token' => 'remote-token', 'expires_in' => 7200]);
        $platform = (new Client(cache: $cache, http: $http))
            ->wechatPlatform(new WechatPlatformConfig('wx_app', 'secret'));

        $this->assertSame('remote-token', $platform->accessToken());
        $this->assertSame(1, $http->requests);
        $this->assertSame(1, $cache->lockCalls);
        $this->assertSame('remote-token', $platform->accessToken());
        $this->assertSame(1, $http->requests);
    }
}

/**
 * 测试用 StoreCacheInterface 内存实现。
 */
final class ArrayCacheStore implements StoreCacheInterface
{
    public int $lockCalls = 0;

    /** @var array<string,mixed> */
    private array $values = [];

    /**
     * 读取测试缓存值。
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    /**
     * 写入缓存值。
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->values[$key] = $value;
    }

    /**
     * 删除缓存值。
     */
    public function del(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * 在锁语义下执行回调。
     */
    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        ++$this->lockCalls;

        return $callback();
    }
}

/**
 * 返回固定 JSON 响应的测试 HTTP 客户端。
 */
final class FakeHttpClient implements ClientInterface
{
    public int $requests = 0;

    /**
     * 创建固定响应测试 HTTP 客户端。
     *
     * @param array<string,mixed> $payload
     */
    public function __construct(private readonly array $payload) {}

    /**
     * 实现测试 HTTP 客户端同步发送接口。
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->response();
    }

    /**
     * 实现测试 HTTP 客户端异步发送接口。
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('sendAsync is not used in this test'));
    }

    /**
     * 实现测试 HTTP 客户端请求接口或记录请求。
     * @param mixed $uri
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        ++$this->requests;

        return $this->response();
    }

    /**
     * 实现测试 HTTP 客户端异步请求接口。
     * @param mixed $uri
     */
    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('requestAsync is not used in this test'));
    }

    /**
     * 返回测试 HTTP 客户端配置。
     */
    public function getConfig(?string $option = null): mixed
    {
        return null;
    }

    /**
     * 构造测试 HTTP 响应对象。
     */
    private function response(): ResponseInterface
    {
        return new Response(200, [], json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }
}
