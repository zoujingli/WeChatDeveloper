<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Exception\ApiException;
use We\Support\JsonClient;

#[CoversClass(JsonClient::class)]
final class JsonClientTest extends TestCase
{
    public function testSendRejectsAbsoluteUri(): void
    {
        $client = new JsonClient();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('相对路径');

        $client->send('GET', 'https://example.com/evil');
    }

    public function testSendRejectsNetworkPathUri(): void
    {
        $client = new JsonClient();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('相对路径');

        $client->send('GET', '//example.com/evil');
    }
}
