<?php

declare(strict_types=1);

namespace We\Common\Transport;

use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use We\Common\Exception\ProtocolException;
use We\Common\Request;

/**
 * 将 `Request` 的唯一请求体编码为最终报文流。
 *
 * @internal
 */
final class BodyEncoder
{
    public function encode(Request $request): EncodedBody
    {
        return match ($request->bodyType) {
            Request::BODY_EMPTY => new EncodedBody(Utils::streamFor(''), null, 0),
            Request::BODY_JSON => $this->json($request->body),
            Request::BODY_FORM => $this->form($request->body),
            Request::BODY_RAW => new EncodedBody(
                Utils::streamFor(is_string($request->body) ? $request->body : ''),
                $request->mediaType,
                $request->bodyLength,
            ),
            Request::BODY_STREAM => $this->stream($request),
            Request::BODY_MULTIPART => $this->multipart($request),
            default => throw new ProtocolException('不支持的请求体类型 `' . $request->bodyType . '`'),
        };
    }

    private function json(mixed $value): EncodedBody
    {
        try {
            $contents = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\JsonException $exception) {
            throw new ProtocolException('JSON 请求体编码失败', 0, $exception);
        }

        return new EncodedBody(Utils::streamFor($contents), 'application/json', strlen($contents));
    }

    /** @param list<array{0:string,1:string}> $pairs */
    private function form(mixed $pairs): EncodedBody
    {
        if (!is_array($pairs)) {
            throw new ProtocolException('表单请求体结构无效');
        }
        $fields = [];
        foreach ($pairs as [$name, $value]) {
            $fields[] = rawurlencode($name) . '=' . rawurlencode($value);
        }
        $contents = implode('&', $fields);

        return new EncodedBody(Utils::streamFor($contents), 'application/x-www-form-urlencoded', strlen($contents));
    }

    private function multipart(Request $request): EncodedBody
    {
        $elements = [];
        foreach ($request->parts as $part) {
            $headers = $part->headers;
            if ($part->mediaType !== null) {
                $headers['Content-Type'] = $part->mediaType;
            }
            $elements[] = [
                'name' => $part->name,
                'contents' => $part->contents,
                'filename' => $part->filename,
                'headers' => $headers,
            ];
        }
        $stream = new MultipartStream($elements);

        return new EncodedBody($stream, 'multipart/form-data; boundary=' . $stream->getBoundary(), $stream->getSize());
    }

    private function stream(Request $request): EncodedBody
    {
        if (!$request->body instanceof StreamInterface) {
            throw new ProtocolException('流请求体结构无效');
        }
        $length = $request->bodyLength;
        if ($length === null) {
            $size = $request->body->getSize();
            if ($size !== null && $request->body->isSeekable()) {
                $length = max(0, $size - $request->body->tell());
            }
        }

        return new EncodedBody($request->body, $request->mediaType, $length);
    }
}
