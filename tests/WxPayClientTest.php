<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\SignatureException;
use We\Common\Exception\StreamException;
use We\Common\Provider\TrustMaterialProviderInterface;
use We\Common\Request;
use We\Common\Runtime;
use We\Wechat\WxPayConfig;
use We\WxPayClient;

/**
 * 微信支付签名、验签和派生资源契约测试。
 *
 * @internal
 * @coversNothing
 */
final class WxPayClientTest extends TestCase
{
    public function testCallSignsExactRequestAndVerifiesResponse(): void
    {
        $body = '{"transaction_id":"T1"}';
        $transport = new RecordingTransport([
            ProtocolFixtures::wxPayResponse(200, $body),
        ]);
        $client = WxPayClient::mk(ProtocolFixtures::wxPayConfig(), new Runtime(transport: $transport));

        $data = $client->call(
            Request::post('v3/pay/transactions/jsapi')
                ->query(['debug' => '1'])
                ->json(['amount' => ['total' => 1]]),
        )->json();

        self::assertSame(['transaction_id' => 'T1'], $data);
        $request = $transport->requests[0];
        self::assertSame('debug=1', $request->getUri()->getQuery());
        self::assertSame('{"amount":{"total":1}}', (string)$request->getBody());
        self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048 ', $request->getHeaderLine('Authorization'));
    }

    public function testRawResponseCannotBypassRequiredVerification(): void
    {
        $client = WxPayClient::mk(
            ProtocolFixtures::wxPayConfig(),
            new Runtime(transport: new RecordingTransport([
                new PsrResponse(200, ['Content-Type' => 'application/octet-stream'], 'UNTRUSTED'),
            ])),
        );

        $this->expectException(SignatureException::class);
        $client->call(Request::get('v3/example'));
    }

    public function testInvalidCustomTrustMaterialBecomesSdkExceptionWithoutWarning(): void
    {
        $base = ProtocolFixtures::wxPayConfig();
        $config = new WxPayConfig(
            $base->appid,
            $base->mchId,
            $base->merchantSigner,
            new class implements TrustMaterialProviderInterface {
                public function publicKey(string $channel, string $keyId): string
                {
                    return 'invalid-public-key';
                }
            },
        );
        $client = WxPayClient::mk($config, new Runtime(transport: new RecordingTransport([
            ProtocolFixtures::wxPayResponse(200, '{"ok":true}'),
        ])));
        set_error_handler(static function (int $severity, string $message): never {
            throw new \ErrorException($message, 0, $severity);
        });

        try {
            $client->call(Request::get('v3/example'));
            self::fail('预期无效自定义信任材料被拒绝');
        } catch (SignatureException $exception) {
            self::assertStringContainsString('信任材料 格式无效', $exception->getMessage());
        } finally {
            restore_error_handler();
        }
    }

    public function testSignedResourceIsAuthorizedAndCheckedBeforeCopy(): void
    {
        $contents = "bill,row\n1,2\n";
        $sourceBody = json_encode([
            'download_url' => 'https://download.example.com/v3/billdownload/file?token=signed',
            'hash_value' => hash('sha256', $contents),
        ], JSON_THROW_ON_ERROR);
        $transport = new RecordingTransport([
            ProtocolFixtures::wxPayResponse(200, $sourceBody),
            new PsrResponse(200, ['Content-Type' => 'text/csv'], $contents),
        ]);
        $client = WxPayClient::mk(ProtocolFixtures::wxPayConfig(), new Runtime(transport: $transport));
        $source = $client->call(Request::get('v3/bill'));
        $destination = Utils::streamFor('');

        $response = $client->call(Request::get(
            $source->signedResource('download_url', 'hash_value'),
        )->downloadTo($destination));

        self::assertSame($contents, (string)$destination);
        self::assertSame(strlen($contents), $response->bytesWritten());
        self::assertSame(hash('sha256', $contents), $response->digest());
        self::assertStringStartsWith('WECHATPAY2-SHA256-RSA2048 ', $transport->requests[1]->getHeaderLine('Authorization'));
        self::assertSame('download.example.com', $transport->requests[1]->getUri()->getHost());
    }

    public function testSensitiveKeyIsProtocolOwnedHeader(): void
    {
        $transport = new RecordingTransport([
            ProtocolFixtures::wxPayResponse(200, '{"ok":true}'),
        ]);
        $client = WxPayClient::mk(ProtocolFixtures::wxPayConfig(), new Runtime(transport: $transport));

        $client->call(
            Request::post('v3/example')
                ->json(['ciphertext' => 'value'])
                ->sensitiveKey('platform-serial'),
        );

        self::assertSame('platform-serial', $transport->requests[0]->getHeaderLine('Wechatpay-Serial'));
    }

    public function testCallerCannotOverrideProtocolSerial(): void
    {
        $transport = new RecordingTransport();
        $client = WxPayClient::mk(ProtocolFixtures::wxPayConfig(), new Runtime(transport: $transport));

        $this->expectException(InvalidCallException::class);
        $client->call(
            Request::post('v3/example')
                ->json([])
                ->headers(['Wechatpay-Serial' => 'caller-controlled']),
        );
    }

    public function testDigestFailureLeavesDestinationUntouched(): void
    {
        $sourceBody = json_encode([
            'url' => 'https://download.example.com/file',
            'hash' => hash('sha256', 'EXPECTED'),
        ], JSON_THROW_ON_ERROR);
        $transport = new RecordingTransport([
            ProtocolFixtures::wxPayResponse(200, $sourceBody),
            new PsrResponse(200, [], 'FILE'),
        ]);
        $client = WxPayClient::mk(ProtocolFixtures::wxPayConfig(), new Runtime(transport: $transport));
        $source = $client->call(Request::get('v3/resource'));
        $destination = Utils::streamFor('');

        try {
            $client->call(Request::get($source->signedResource('url', 'hash'))->downloadTo($destination));
            self::fail('预期摘要校验失败');
        } catch (StreamException) {
            self::assertSame('', (string)$destination);
        }
    }
}
