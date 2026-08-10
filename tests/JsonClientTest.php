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
use We\Exception\ApiException;
use We\Support\JsonClient;

/**
 * JSON HTTP 客户端安全约束测试用例。
 * @internal
 */
#[CoversClass(JsonClient::class)]
final class JsonClientTest extends TestCase
{
    /**
     * 测试 JSON 客户端拒绝绝对 URL。
     */
    public function testSendRejectsAbsoluteUri(): void
    {
        $client = new JsonClient();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('相对路径');

        $client->send('GET', 'https://example.com/evil');
    }

    /**
     * 测试 JSON 客户端拒绝网络路径 URL。
     */
    public function testSendRejectsNetworkPathUri(): void
    {
        $client = new JsonClient();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('相对路径');

        $client->send('GET', '//example.com/evil');
    }

    /**
     * 测试默认关闭 Guzzle http_errors，并解析非 2xx JSON 错误响应。
     */
    public function testRequestDisablesHttpErrorsAndParsesErrorPayload(): void
    {
        $http = new JsonClientFakeHttpClient(new Response(400, [], '{"errcode":40001,"errmsg":"invalid credential"}'));
        $client = new JsonClient($http);

        try {
            $client->request('GET', 'cgi-bin/token');
            self::fail('Expected ApiException was not thrown.');
        } catch (ApiException $exception) {
            self::assertSame(40001, $exception->getCode());
            self::assertSame('invalid credential', $exception->getMessage());
            self::assertSame(false, $http->requests[0]['options']['http_errors']);
            self::assertSame(40001, $exception->context()['errcode']);
        }
    }
}

/**
 * JSON 客户端测试用 HTTP 客户端。
 */
final class JsonClientFakeHttpClient implements ClientInterface
{
    /** @var array<int,array{method:string,uri:mixed,options:array<string,mixed>}> */
    public array $requests = [];

    /**
     * 创建固定响应测试客户端。
     */
    public function __construct(private readonly ResponseInterface $response) {}

    /**
     * 实现测试 HTTP 客户端同步发送接口。
     */
    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->response;
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
     * @param mixed $uri
     */
    public function request(string $method, $uri = '', array $options = []): ResponseInterface
    {
        $this->requests[] = ['method' => $method, 'uri' => $uri, 'options' => $options];

        return $this->response;
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
}
