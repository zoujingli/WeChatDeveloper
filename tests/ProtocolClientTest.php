<?php

declare(strict_types=1);

/**
 * 协议层原始响应、下载和上传能力测试。
 */

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
use We\Config\WechatPaymentConfig;
use We\Config\WechatPlatformConfig;
use We\Config\WechatServiceConfig;
use We\Contract\StoreCacheInterface;
use We\Contract\StoreTokenInterface;
use We\Platform\Wechat\PaymentClient as WechatPaymentClient;
use We\Platform\Wechat\PlatformClient as WechatPlatformClient;
use We\Platform\Wechat\ServiceClient as WechatServiceClient;
use We\Support\CacheKey;
use We\Support\JsonClient;
use We\Support\TokenCacheKey;

/**
 * 协议层原始响应、下载和上传能力测试用例。
 */
#[CoversClass(JsonClient::class)]
#[CoversClass(WechatPlatformClient::class)]
#[CoversClass(WechatPaymentClient::class)]
#[CoversClass(WechatServiceClient::class)]
final class ProtocolClientTest extends TestCase
{
    /**
     * 测试 raw 返回原始响应，不执行 JSON 解析。
     */
    public function testJsonClientRawReturnsResponseWithoutJsonParsing(): void
    {
        $http = new ProtocolHttpClient([new Response(200, ['Content-Type' => 'image/png'], 'PNG-DATA')]);
        $client = new JsonClient($http);

        $response = $client->raw('GET', 'cgi-bin/media/get', ['media_id' => 'm1']);

        $this->assertSame('PNG-DATA', (string)$response->getBody());
        $this->assertSame(['media_id' => 'm1'], $http->requests[0]['options']['query']);
    }

    /**
     * 测试微信公众平台下载接口返回二进制内容且自动附加 access_token。
     */
    public function testWechatPlatformDownloadReturnsBinaryWithAccessToken(): void
    {
        $cache = new ProtocolCacheStore();
        $cache->set(CacheKey::compose(
            Client::DEFAULT_CACHE_KEY_PREFIX,
            'wechat.platform',
            TokenCacheKey::wechatPlatformAccessToken('wx_app'),
        ), 'cached-token', 3600);
        $http = new ProtocolHttpClient([new Response(200, ['Content-Type' => 'image/jpeg'], 'JPEG-DATA')]);
        $platform = (new Client(cache: $cache, http: $http))->wechatPlatform(new WechatPlatformConfig('wx_app', 'secret'));

        $response = $platform->download('cgi-bin/media/get', ['media_id' => 'm1']);

        $this->assertSame('JPEG-DATA', (string)$response->getBody());
        $this->assertSame('cached-token', $http->requests[0]['options']['query']['access_token']);
        $this->assertSame('m1', $http->requests[0]['options']['query']['media_id']);
    }

    /**
     * 测试微信公众平台上传接口透传 multipart 并解析 JSON 响应。
     */
    public function testWechatPlatformUploadSendsMultipartAndParsesJson(): void
    {
        $cache = new ProtocolCacheStore();
        $cache->set(CacheKey::compose(
            Client::DEFAULT_CACHE_KEY_PREFIX,
            'wechat.platform',
            TokenCacheKey::wechatPlatformAccessToken('wx_app'),
        ), 'cached-token', 3600);
        $http = new ProtocolHttpClient([new Response(200, [], '{"media_id":"MEDIA_ID"}')]);
        $platform = (new Client(cache: $cache, http: $http))->wechatPlatform(new WechatPlatformConfig('wx_app', 'secret'));
        $multipart = [
            ['name' => 'media', 'contents' => 'file-content', 'filename' => 'demo.jpg'],
        ];

        $data = $platform->upload('cgi-bin/media/upload', $multipart, ['type' => 'image']);

        $this->assertSame('MEDIA_ID', $data['media_id']);
        $this->assertSame($multipart, $http->requests[0]['options']['multipart']);
        $this->assertSame('cached-token', $http->requests[0]['options']['query']['access_token']);
        $this->assertSame('image', $http->requests[0]['options']['query']['type']);
    }

    /**
     * 测试微信支付下载接口会生成 APIv3 Authorization 签名并返回原始响应。
     */
    public function testWechatPaymentDownloadSignsRequestAndReturnsRawResponse(): void
    {
        [$merchantPrivateKey] = self::keyPair();
        $http = new ProtocolHttpClient([new Response(200, ['Content-Type' => 'text/plain'], 'BILL-DATA')]);
        $payment = new WechatPaymentClient(new WechatPaymentConfig(
            'wx_app',
            'mch_id',
            str_repeat('k', 32),
            'merchant-serial',
            $merchantPrivateKey,
        ), $http);

        $response = $payment->download('v3/bill/tradebill', ['bill_date' => '2026-05-08']);

        $this->assertSame('BILL-DATA', (string)$response->getBody());
        $headers = $http->requests[0]['options']['headers'];
        $this->assertStringStartsWith('WECHATPAY2-SHA256-RSA2048 ', (string)$headers['Authorization']);
        $this->assertSame('merchant-serial', $headers['Wechatpay-Serial']);
        $this->assertSame('2026-05-08', $http->requests[0]['options']['query']['bill_date']);
    }

    /**
     * 测试微信服务平台代授权方 GET 调用会把 params 作为 query 并附加授权方 access_token。
     */
    public function testWechatServiceAuthorizerGetUsesParamsAsQuery(): void
    {
        $cache = new ProtocolCacheStore();
        $cache->set(CacheKey::compose(
            Client::DEFAULT_CACHE_KEY_PREFIX,
            'wechat.service',
            TokenCacheKey::wechatServiceAuthorizerAccessToken('component_app', 'authorizer_app'),
        ), 'authorizer-token', 3600);
        $http = new ProtocolHttpClient([new Response(200, [], '{"ok":true}')]);
        $service = (new Client(cache: $cache, authorizers: new ProtocolAuthorizerTokenStore(), http: $http))
            ->wechatService(new WechatServiceConfig('component_app', 'component_secret', 'component_token', 'encoding_key'));

        $data = $service->get('cgi-bin/user/get', ['next_openid' => 'NEXT'], [
            'authorizer_appid' => 'authorizer_app',
            'component_access_token' => 'component-token',
        ]);

        $this->assertTrue($data['ok']);
        $this->assertSame('NEXT', $http->requests[0]['options']['query']['next_openid']);
        $this->assertSame('authorizer-token', $http->requests[0]['options']['query']['access_token']);
    }

    /**
     * 生成测试使用的 RSA 密钥对。
     *
     * @return array{0:string,1:string}
     */
    private static function keyPair(): array
    {
        $resource = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        self::assertNotFalse($resource);
        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);

        return [$privateKey, (string)$details['key']];
    }
}

/**
 * 协议层测试用 HTTP 客户端。
 */
final class ProtocolHttpClient implements ClientInterface
{
    /** @var array<int,array{method:string,uri:mixed,options:array<string,mixed>}> */
    public array $requests = [];

    /**
     * 创建协议层测试 HTTP 客户端。
     *
     * @param array<int,ResponseInterface> $responses
     */
    public function __construct(private array $responses) {}

    /**
     * 实现测试 HTTP 客户端同步发送接口。
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->nextResponse();
    }

    /**
     * 实现测试 HTTP 客户端异步发送接口。
     */
    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return Create::rejectionFor(new \RuntimeException('sendAsync is not used in this test'));
    }

    /**
     * 实现测试 HTTP 客户端请求接口并记录请求。
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'uri' => $uri, 'options' => $options];

        return $this->nextResponse();
    }

    /**
     * 实现测试 HTTP 客户端异步请求接口。
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
     * 返回下一个测试响应。
     */
    private function nextResponse(): ResponseInterface
    {
        return array_shift($this->responses) ?? new Response(200, [], '{}');
    }
}

/**
 * 协议层测试用缓存实现。
 */
final class ProtocolCacheStore implements StoreCacheInterface
{
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
     * 写入测试缓存值。
     */
    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->values[$key] = $value;
    }

    /**
     * 删除测试缓存值。
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
        return $callback();
    }
}

/**
 * 协议层测试用授权方 Token 仓库。
 */
final class ProtocolAuthorizerTokenStore implements StoreTokenInterface
{
    /**
     * 返回测试授权方刷新凭据。
     */
    public function refreshToken(string $authorizerAppid): string
    {
        return 'refresh-token';
    }

    /**
     * 忽略授权方 Token 回写。
     *
     * @param array<string,mixed> $payload
     */
    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void {}
}
