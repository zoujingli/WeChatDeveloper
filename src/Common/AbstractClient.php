<?php

declare(strict_types=1);

namespace We\Common;

use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use We\Common\Contract\ChannelInterface;
use We\Common\Exception\InvalidCallException;
use We\Common\Exception\PlatformException;
use We\Common\Exception\ProtocolException;
use We\Common\Exception\SdkException;
use We\Common\Exception\StreamException;
use We\Common\Exception\TransportException;
use We\Common\Internal\RequestState;
use We\Common\Protocol\ExternalResourcePolicyInterface;
use We\Common\Support\XmlCodec;
use We\Common\Transport\BodyEncoder;
use We\Common\Transport\EncodedBody;
use We\Common\Transport\HttpTransportInterface;
use We\Common\Transport\Spool;
use We\Common\Transport\Spooler;

/**
 * 六个通道 Client 共用的请求校验、传输、验签和响应转换管线。
 *
 * @internal
 */
abstract class AbstractClient implements ChannelInterface
{
    private const RESERVED_HEADERS = [
        'authorization',
        'host',
        'content-length',
        'content-type',
        'wechatpay-signature',
        'wechatpay-serial',
        'alipay-app-auth-token',
        'alipay-signature',
        'alipay-sn',
        'alipay-timestamp',
        'alipay-nonce',
    ];

    protected function __construct(
        protected readonly HttpTransportInterface $transport,
        protected readonly Spooler $spooler,
        protected readonly ExternalResourcePolicyInterface $resourcePolicy,
        private readonly BodyEncoder $bodyEncoder = new BodyEncoder(),
    ) {}

    /** 完成一次出站 API 调用并返回已完成协议校验的响应。 */
    public function call(Request $request): Response
    {
        $state = $request->internalState();
        $this->assertHeaders($state);
        $this->validateRequest($state);
        $credentials = $this->resolveCredentials($state);
        $body = $this->bodyEncoder->encode($state);
        $outbound = $this->buildRequest($state, $body, $credentials);
        try {
            $response = $this->transport->send($outbound, $state->timeoutMilliseconds);
        } catch (TransportException $exception) {
            throw new TransportException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception,
                $exception->context(),
                $this->channel(),
                $exception->requestId(),
                $exception->platformCode(),
            );
        }
        [$requestId, $keyId] = $this->metadata($response);
        $spool = null;

        try {
            $spool = $this->spooler->spool($response->getBody(), $state->maxResponseBytes);
            $contents = $spool->contents();
            $this->verifyResponse($state, $response, $contents);

            return $this->response($state, $response, $spool, $contents, $requestId, $keyId);
        } catch (SdkException $exception) {
            $spool?->stream->close();
            throw $this->enrichResponseException($exception, $requestId);
        } catch (\Throwable $exception) {
            $spool?->stream->close();
            throw $exception;
        }
    }

    abstract protected function validateRequest(RequestState $request): void;

    /** @return array<string,string> */
    protected function resolveCredentials(RequestState $request): array
    {
        return [];
    }

    /** @param array<string,string> $credentials */
    abstract protected function buildRequest(RequestState $request, EncodedBody $body, array $credentials): RequestInterface;

    /**
     * 返回参与签名的精确字节，并在流不可回绕时用受限临时流替换请求体。
     *
     * @return array{0:string,1:EncodedBody}
     */
    protected function signableBody(RequestState $request, EncodedBody $body): array
    {
        if ($body->stream->isSeekable()) {
            $position = $body->stream->tell();
            $contents = $body->stream->getContents();
            $body->stream->seek($position);

            return [$contents, $body];
        }
        $spool = $this->spooler->spool($body->stream, $request->maxResponseBytes);

        return [$spool->contents(), new EncodedBody($spool->stream, $body->contentType, $spool->bytes)];
    }

    /** @param array<string,list<string>|string> $protocolHeaders */
    protected function httpRequest(
        RequestState $request,
        UriInterface $uri,
        EncodedBody $body,
        array $protocolHeaders = [],
    ): RequestInterface {
        $headers = $request->headerMap();
        foreach ($protocolHeaders as $name => $values) {
            $headers[$name] = is_array($values) ? $values : [$values];
        }
        if ($body->contentType !== null) {
            $headers['Content-Type'] = [$body->contentType];
        }
        if ($body->contentLength !== null) {
            $headers['Content-Length'] = [(string)$body->contentLength];
        }

        return new PsrRequest($request->method, $uri, $headers, $body->stream);
    }

    protected function verifyResponse(RequestState $request, ResponseInterface $response, ?string $body): void {}

    protected function assertJsonSuccess(RequestState $request, ResponseInterface $response, mixed $value): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '平台返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $this->channel(),
            );
        }
    }

    /** @param array<string,mixed> $value */
    protected function assertXmlSuccess(RequestState $request, ResponseInterface $response, array $value): void
    {
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '平台返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $this->channel(),
            );
        }
    }

    protected function responseKeyId(ResponseInterface $response): ?string
    {
        return null;
    }

    protected function normalizeJson(RequestState $request, ResponseInterface $response, mixed $value): mixed
    {
        return $value;
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    protected function normalizeXml(RequestState $request, ResponseInterface $response, array $value): array
    {
        return $value;
    }

    private function assertHeaders(RequestState $request): void
    {
        foreach (self::RESERVED_HEADERS as $header) {
            if ($request->hasHeader($header)) {
                throw new InvalidCallException(
                    '调用方不能覆盖协议保留请求头: ' . $header,
                    channel: $this->channel(),
                );
            }
        }
    }

    private function response(
        RequestState $request,
        ResponseInterface $response,
        Spool $spool,
        string $contents,
        ?string $requestId,
        ?string $keyId,
    ): Response {
        if ($request->destination !== null) {
            return $this->streamResponse($request, $response, $spool, $contents, $requestId, $keyId);
        }
        if ($spool->bytes === 0) {
            if ($response->getStatusCode() >= 400) {
                throw new PlatformException(
                    '平台返回 HTTP 错误',
                    context: ['status' => $response->getStatusCode()],
                    channel: $this->channel(),
                    platformCode: $response->getStatusCode(),
                );
            }
            $spool->stream->close();

            return new Response(
                Response::FORMAT_EMPTY,
                Utils::streamFor(''),
                null,
                null,
                $this->channel(),
                $response->getStatusCode(),
                $response->getHeaders(),
                $requestId,
                $keyId,
            );
        }
        $format = $this->structuredFormat($response, $contents);
        if ($format === Response::FORMAT_JSON) {
            try {
                $value = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ProtocolException('平台 JSON 响应格式无效', 0, $exception, channel: $this->channel());
            }
            $value = $this->normalizeJson($request, $response, $value);
            $this->assertJsonSuccess($request, $response, $value);
            $spool->stream->close();

            return new Response(
                Response::FORMAT_JSON,
                Utils::streamFor($contents),
                $value,
                null,
                $this->channel(),
                $response->getStatusCode(),
                $response->getHeaders(),
                $requestId,
                $keyId,
            );
        }
        if ($format === Response::FORMAT_XML) {
            $value = XmlCodec::decode($contents);
            $value = $this->normalizeXml($request, $response, $value);
            $this->assertXmlSuccess($request, $response, $value);
            $spool->stream->close();

            return new Response(
                Response::FORMAT_XML,
                Utils::streamFor($contents),
                null,
                $value,
                $this->channel(),
                $response->getStatusCode(),
                $response->getHeaders(),
                $requestId,
                $keyId,
            );
        }
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '平台返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $this->channel(),
                platformCode: $response->getStatusCode(),
            );
        }

        return new Response(
            Response::FORMAT_RAW,
            $spool->stream,
            null,
            null,
            $this->channel(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $requestId,
            $keyId,
        );
    }

    private function streamResponse(
        RequestState $request,
        ResponseInterface $response,
        Spool $spool,
        string $contents,
        ?string $requestId,
        ?string $keyId,
    ): Response {
        $format = $this->structuredFormat($response, $contents);
        if ($format === Response::FORMAT_JSON) {
            try {
                $value = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ProtocolException('流响应中的 JSON 错误格式无效', 0, $exception, channel: $this->channel());
            }
            $value = $this->normalizeJson($request, $response, $value);
            $this->assertJsonSuccess($request, $response, $value);
            throw new ProtocolException('预期二进制响应但平台返回 JSON', channel: $this->channel());
        }
        if ($format === Response::FORMAT_XML) {
            $value = XmlCodec::decode($contents);
            $value = $this->normalizeXml($request, $response, $value);
            $this->assertXmlSuccess($request, $response, $value);
            throw new ProtocolException('预期二进制响应但平台返回 XML', channel: $this->channel());
        }
        if ($response->getStatusCode() >= 400) {
            throw new PlatformException(
                '平台资源返回 HTTP 错误',
                context: ['status' => $response->getStatusCode()],
                channel: $this->channel(),
                platformCode: $response->getStatusCode(),
            );
        }
        $algorithm = $request->digestAlgorithm;
        $expected = $request->expectedDigest;
        if ($request->target instanceof Resource && $request->target->digestAlgorithm !== null) {
            $algorithm = $request->target->digestAlgorithm;
            $expected = $request->target->expectedDigest;
        }
        $digest = $algorithm === null ? null : $spool->digest($algorithm);
        if ($digest !== null && $expected !== null && !hash_equals(strtolower($expected), strtolower($digest))) {
            throw new StreamException('派生资源摘要校验失败', channel: $this->channel());
        }
        $destination = $request->destination;
        if ($destination === null) {
            throw new InvalidCallException('下载目标流不能为空', channel: $this->channel());
        }
        $bytes = $this->spooler->copy($spool, $destination);
        $spool->stream->close();

        return new Response(
            Response::FORMAT_STREAM,
            $destination,
            null,
            null,
            $this->channel(),
            $response->getStatusCode(),
            $response->getHeaders(),
            $requestId,
            $keyId,
            $bytes,
            $digest,
        );
    }

    /** @return array{0:?string,1:?string} */
    private function metadata(ResponseInterface $response): array
    {
        $requestId = null;
        foreach (['Request-Id', 'Wechatpay-Request-Id', 'Alipay-Trace-Id'] as $name) {
            if ($response->hasHeader($name)) {
                $requestId = $response->getHeaderLine($name);
                break;
            }
        }

        return [$requestId, $this->responseKeyId($response)];
    }

    private function structuredFormat(ResponseInterface $response, string $contents): ?string
    {
        $contentType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'), 2)[0]));
        if ($contentType !== '') {
            if (str_ends_with($contentType, '/json') || str_ends_with($contentType, '+json')) {
                return Response::FORMAT_JSON;
            }
            if (str_ends_with($contentType, '/xml') || str_ends_with($contentType, '+xml')) {
                return Response::FORMAT_XML;
            }

            return null;
        }
        $first = substr(ltrim($contents), 0, 1);
        if (in_array($first, ['{', '['], true)) {
            return Response::FORMAT_JSON;
        }
        if ($first === '<') {
            return Response::FORMAT_XML;
        }

        return null;
    }

    private function enrichResponseException(SdkException $exception, ?string $requestId): SdkException
    {
        if ($exception->channel() === $this->channel() && $exception->requestId() === $requestId) {
            return $exception;
        }
        $class = $exception::class;

        return new $class(
            $exception->getMessage(),
            $exception->getCode(),
            $exception,
            $exception->context(),
            $exception->channel() ?? $this->channel(),
            $exception->requestId() ?? $requestId,
            $exception->platformCode(),
        );
    }
}
