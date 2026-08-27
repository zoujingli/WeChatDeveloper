<?php

declare(strict_types=1);

namespace We\Common\Transport;

use Psr\Http\Message\StreamInterface;

/**
 * 最终请求体流及其内容类型和长度。
 *
 * @internal
 */
final class EncodedBody
{
    public function __construct(
        public readonly StreamInterface $stream,
        public readonly ?string $contentType,
        public readonly ?int $contentLength,
    ) {}
}
