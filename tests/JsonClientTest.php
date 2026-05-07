<?php

declare(strict_types=1);

/**
 * JSON HTTP 客户端安全约束测试。
 */

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Exception\ApiException;
use We\Support\JsonClient;

/**
 * JSON HTTP 客户端安全约束测试用例。
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
}
