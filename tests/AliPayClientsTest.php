<?php

declare(strict_types=1);

namespace We\Tests;

use GuzzleHttp\Psr7\Response as PsrResponse;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use We\Alipay\AliRestConfig;
use We\Alipay\Common\StaticTokenProvider;
use We\Alipay\Common\TokenKind;
use We\Alipay\Common\TokenProviderInterface;
use We\AliPayClient;
use We\AliRestClient;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\SignatureException;
use We\Common\MultipartPart;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\SigningKeyProviderInterface;
use We\Common\Request;
use We\Common\Runtime;

/**
 * 支付宝支付 v2 与 REST v3 通道契约测试。
 *
 * @internal
 * @coversNothing
 */
final class AliPayClientsTest extends TestCase
{
    public function testGatewaySignsMethodAndParsesVerifiedResponseNode(): void
    {
        $method = 'alipay.trade.query';
        $transport = new RecordingTransport([
            ProtocolFixtures::aliPayResponse($method, ['code' => '10000', 'trade_no' => 'A1']),
        ]);
        $client = AliPayClient::mk(ProtocolFixtures::aliPayConfig(), new Runtime(transport: $transport));

        $data = $client->call(Request::post($method)->json(['out_trade_no' => 'ORDER-1']))->json();

        self::assertSame('A1', $data['trade_no']);
        parse_str((string)$transport->requests[0]->getBody(), $fields);
        self::assertSame($method, $fields['method']);
        self::assertSame('{"out_trade_no":"ORDER-1"}', $fields['biz_content']);
        self::assertNotSame('', $fields['sign']);
    }

    public function testGatewayJsonBodyIsEncodedOnlyOnce(): void
    {
        $method = 'alipay.trade.query';
        $payload = new class implements \JsonSerializable {
            public int $calls = 0;

            public function jsonSerialize(): mixed
            {
                ++$this->calls;

                return ['out_trade_no' => 'ORDER-1'];
            }
        };
        $client = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig(),
            new Runtime(transport: new RecordingTransport([
                ProtocolFixtures::aliPayResponse($method, ['code' => '10000']),
            ])),
        );

        $client->call(Request::post($method)->json($payload));

        self::assertSame(1, $payload->calls);
    }

    public function testGatewayUsesReferencedUserToken(): void
    {
        $method = 'alipay.user.info.share';
        $transport = new RecordingTransport([
            ProtocolFixtures::aliPayResponse($method, ['code' => '10000']),
        ]);
        $tokens = new StaticTokenProvider([
            TokenKind::AlipayUser->value => ['user-1' => 'USER-TOKEN'],
        ]);
        $client = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig(),
            new Runtime(transport: $transport, tokens: $tokens),
        );

        $client->call(Request::post($method)->json([])->asAlipayUser('user-1'));

        parse_str((string)$transport->requests[0]->getBody(), $fields);
        self::assertSame('USER-TOKEN', $fields['auth_token']);
    }

    public function testGatewaySupportsVerifiedXmlAndRawMedia(): void
    {
        $method = 'alipay.example.query';
        $node = str_replace('.', '_', $method) . '_response';
        $inner = '<code>10000</code><item>A</item><item>B</item>';
        $signed = $inner . '</' . $node . '>';
        $signer = new PemSigningKeyProvider('default', TestKeys::platformKeyPair()[0]);
        $xml = '<alipay><' . $node . '>' . $inner . '</' . $node . '><sign>' . $signer->sign($signed) . '</sign></alipay>';
        $transport = new RecordingTransport([
            new PsrResponse(200, ['Content-Type' => 'application/xml'], $xml),
            new PsrResponse(200, ['Content-Type' => 'image/png'], 'PNG'),
            new PsrResponse(400, ['Content-Type' => 'application/json'], '{"error_response":{"code":"40004","msg":"failed"}}'),
        ]);
        $xmlClient = AliPayClient::mk(ProtocolFixtures::aliPayConfig('XML'), new Runtime(transport: $transport));

        $value = $xmlClient->call(Request::post($method)->json([]))->xml();
        $raw = AliPayClient::mk(ProtocolFixtures::aliPayConfig(), new Runtime(transport: $transport))
            ->call(Request::get('alipay.mobile.public.multimedia.download')->rawMedia());

        self::assertSame(['A', 'B'], $value['item']);
        self::assertSame('PNG', $raw->raw());
        $raw->body()->close();

        try {
            AliPayClient::mk(ProtocolFixtures::aliPayConfig(), new Runtime(transport: $transport))
                ->call(Request::get('alipay.mobile.public.multimedia.download')->rawMedia());
            self::fail('预期媒体响应中的平台错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame('40004', $exception->platformCode());
        }
    }

    public function testGatewayRawMediaRejectsXmlErrorBeforeDownload(): void
    {
        $xml = '<alipay><error_response><code>40004</code><msg>failed</msg></error_response></alipay>';
        $destination = Utils::streamFor('');
        $client = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig('XML'),
            new Runtime(transport: new RecordingTransport([
                new PsrResponse(200, ['Content-Type' => 'application/xml'], $xml),
            ])),
        );

        try {
            $client->call(
                Request::get('alipay.mobile.public.multimedia.download')->rawMedia()->downloadTo($destination),
            );
            self::fail('预期媒体响应中的 XML 平台错误被拒绝');
        } catch (PlatformException $exception) {
            self::assertSame('40004', $exception->platformCode());
        }
        self::assertSame('', (string)$destination);
    }

    public function testGatewayGetRejectsMultipartWithoutSending(): void
    {
        $transport = new RecordingTransport();
        $client = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig(),
            new Runtime(transport: $transport),
        );

        try {
            $client->call(Request::get('alipay.example.upload')->multipart(
                new MultipartPart('file', 'FILE', 'demo.txt'),
            ));
            self::fail('预期 Gateway GET multipart 被拒绝');
        } catch (InvalidCallException) {
            self::assertSame([], $transport->requests);
        }
    }

    public function testGatewayRejectsDuplicateSignedNodes(): void
    {
        $method = 'alipay.trade.query';
        $node = str_replace('.', '_', $method) . '_response';
        $signed = '{"code":"10000","trusted":true}';
        $signature = (new PemSigningKeyProvider('default', TestKeys::platformKeyPair()[0]))->sign($signed);
        $body = '{"' . $node . '":' . $signed . ',"' . $node . '":{"code":"10000","trusted":false},"sign":"' . $signature . '"}';
        $client = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig(),
            new Runtime(transport: new RecordingTransport([new PsrResponse(200, [], $body)])),
        );

        $this->expectException(SignatureException::class);
        $client->call(Request::post($method)->json([]));
    }

    public function testGatewayAndRestRejectCallerOwnedProtocolParameters(): void
    {
        $gatewayTransport = new RecordingTransport();
        $gateway = AliPayClient::mk(
            ProtocolFixtures::aliPayConfig(),
            new Runtime(transport: $gatewayTransport),
        );
        try {
            $gateway->call(Request::post('alipay.trade.query')->form(['app_id' => 'caller']));
            self::fail('预期调用方提供的 AOP 公共参数被拒绝');
        } catch (InvalidCallException) {
            self::assertSame([], $gatewayTransport->requests);
        }

        $restTransport = new RecordingTransport();
        $rest = AliRestClient::mk(ProtocolFixtures::aliRestConfig(), new Runtime(transport: $restTransport));
        try {
            $rest->call(Request::get('v3/example')->query(['auth_token' => 'caller']));
            self::fail('预期调用方提供的 REST Token 被拒绝');
        } catch (InvalidCallException) {
            self::assertSame([], $restTransport->requests);
        }
    }

    public function testInvalidCallsFailBeforeTokenProviderLookup(): void
    {
        $tokens = new class implements TokenProviderInterface {
            public int $calls = 0;

            public function token(TokenKind $kind, string $credentialId): string
            {
                ++$this->calls;

                return 'TOKEN';
            }
        };
        $runtime = new Runtime(transport: $transport = new RecordingTransport(), tokens: $tokens);

        foreach ([
            static fn () => AliPayClient::mk(ProtocolFixtures::aliPayConfig(), $runtime)->call(
                Request::post('alipay.trade.query')->raw('invalid')->asAlipayUser('user-1'),
            ),
            static fn () => AliRestClient::mk(ProtocolFixtures::aliRestConfig(), $runtime)->call(
                Request::post('v3/example/upload')->multipart(
                    new MultipartPart('invalid', 'value'),
                )->asAlipayApp('app-1'),
            ),
        ] as $call) {
            try {
                $call();
                self::fail('预期无效调用在读取 Token 前被拒绝');
            } catch (InvalidCallException) {
            }
        }

        self::assertSame(0, $tokens->calls);
        self::assertSame([], $transport->requests);
    }

    public function testRestSignsUriBodyAndAppIdentity(): void
    {
        $transport = new RecordingTransport([
            ProtocolFixtures::aliRestResponse(200, '{"id":"R1"}'),
        ]);
        $tokens = new StaticTokenProvider([
            TokenKind::AlipayApp->value => ['merchant-1' => 'APP-AUTH-TOKEN'],
        ]);
        $client = AliRestClient::mk(
            ProtocolFixtures::aliRestConfig(),
            new Runtime(transport: $transport, tokens: $tokens),
        );

        $data = $client->call(
            Request::post('v3/example/resources')
                ->query(['expand' => 'detail'])
                ->json(['name' => 'demo'])
                ->asAlipayApp('merchant-1'),
        )->json();

        self::assertSame('R1', $data['id']);
        $request = $transport->requests[0];
        self::assertSame('expand=detail', $request->getUri()->getQuery());
        self::assertSame('APP-AUTH-TOKEN', $request->getHeaderLine('alipay-app-auth-token'));
        self::assertStringStartsWith('ALIPAY-SHA256withRSA ', $request->getHeaderLine('Authorization'));
        self::assertSame('{"name":"demo"}', (string)$request->getBody());
    }

    public function testRestMultipartSignsDataWithoutFileWireBytes(): void
    {
        $transport = new RecordingTransport([
            ProtocolFixtures::aliRestResponse(200, '{"id":"R2"}'),
        ]);
        $client = AliRestClient::mk(ProtocolFixtures::aliRestConfig(), new Runtime(transport: $transport));
        $data = '{"title":"demo"}';

        $client->call(Request::post('v3/example/upload')->multipart(
            new MultipartPart('data', $data),
            new MultipartPart('file', 'FILE-BYTES', 'demo.txt', 'text/plain'),
        ));

        $request = $transport->requests[0];
        $authorization = $request->getHeaderLine('Authorization');
        self::assertMatchesRegularExpression('/^ALIPAY-SHA256withRSA (.+),sign=([^,]+)$/', $authorization);
        preg_match('/^ALIPAY-SHA256withRSA (.+),sign=([^,]+)$/', $authorization, $matches);
        $signature = base64_decode($matches[2], true);
        $publicKey = openssl_pkey_get_public(TestKeys::publicKey());
        self::assertIsString($signature);
        self::assertNotFalse($publicKey);
        self::assertSame(1, openssl_verify(
            $matches[1] . "\nPOST\n/v3/example/upload\n" . $data . "\n",
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        ));
        self::assertStringContainsString('FILE-BYTES', (string)$request->getBody());
    }

    public function testRestUnsignedResponseFailsClosed(): void
    {
        $client = AliRestClient::mk(
            ProtocolFixtures::aliRestConfig(),
            new Runtime(transport: new RecordingTransport([
                new PsrResponse(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
            ])),
        );

        $this->expectException(SignatureException::class);
        $client->call(Request::get('v3/example'));
    }

    public function testInvalidCustomTokenAndSignatureStayInSdkExceptions(): void
    {
        $base = ProtocolFixtures::aliRestConfig();
        $invalidToken = new class implements TokenProviderInterface {
            public function token(TokenKind $kind, string $credentialId): string
            {
                return "bad\r\nX-Evil: yes";
            }
        };
        try {
            AliRestClient::mk($base, new Runtime(
                transport: new RecordingTransport(),
                tokens: $invalidToken,
            ))->call(Request::get('v3/example')->asAlipayApp('app-1'));
            self::fail('预期自定义 Token Provider 非法值被拒绝');
        } catch (ConfigurationException) {
        }

        $invalidSigner = new class implements SigningKeyProviderInterface {
            public function keyId(): string
            {
                return 'application';
            }

            public function sign(string $message, int|string $algorithm = OPENSSL_ALGO_SHA256): string
            {
                return "bad\r\nsignature";
            }
        };
        $config = new AliRestConfig($base->appid, $invalidSigner, $base->trust);
        try {
            AliRestClient::mk($config, new Runtime(transport: new RecordingTransport()))
                ->call(Request::get('v3/example'));
            self::fail('预期自定义签名 Provider 非法值被拒绝');
        } catch (SignatureException $exception) {
            self::assertStringContainsString('Base64', $exception->getMessage());
        }
    }
}
