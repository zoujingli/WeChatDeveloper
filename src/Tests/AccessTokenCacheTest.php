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
use We\Platform\Wechat\PlatformClient;
use We\Support\CacheKey;
use We\Support\TokenCacheKey;

#[CoversClass(PlatformClient::class)]
final class AccessTokenCacheTest extends TestCase
{
    public function testAccessTokenCacheHitDoesNotRequestHttp(): void
    {
        $cache = new ArrayCacheStore();
        $key = CacheKey::compose(
            Client::DEFAULT_CACHE_KEY_PREFIX,
            'wechat.platform',
            TokenCacheKey::wechatOfficialAccessToken('wx_app'),
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

final class ArrayCacheStore implements StoreCacheInterface
{
    /** @var array<string,mixed> */
    private array $values = [];

    public int $lockCalls = 0;

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->values[$key] = $value;
    }

    public function del(string $key): void
    {
        unset($this->values[$key]);
    }

    public function lock(string $key, int $ttl, callable $callback): mixed
    {
        ++$this->lockCalls;

        return $callback();
    }
}

final class FakeHttpClient implements ClientInterface
{
    public int $requests = 0;

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(private readonly array $payload) {}

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->response();
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('sendAsync is not used in this test'));
    }

    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        ++$this->requests;

        return $this->response();
    }

    public function requestAsync(string $method, $uri = '', array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('requestAsync is not used in this test'));
    }

    public function getConfig(?string $option = null): mixed
    {
        return null;
    }

    private function response(): ResponseInterface
    {
        return new Response(200, [], json_encode($this->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}');
    }
}
