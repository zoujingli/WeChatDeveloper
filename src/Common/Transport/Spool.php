<?php

declare(strict_types=1);

namespace We\Common\Transport;

use Psr\Http\Message\StreamInterface;
use We\Common\Exception\StreamException;

/**
 * 已完整写入本地临时文件的响应数据。
 *
 * @internal
 */
final class Spool
{
    public function __construct(
        public readonly StreamInterface $stream,
        public readonly int $bytes,
    ) {}

    public function contents(): string
    {
        try {
            $this->stream->rewind();
            $contents = $this->stream->getContents();
            $this->stream->rewind();
        } catch (\RuntimeException $exception) {
            throw new StreamException('读取协议临时流失败', 0, $exception);
        }

        return $contents;
    }

    public function digest(string $algorithm): string
    {
        try {
            $this->stream->rewind();
            $context = hash_init($algorithm);
            while (!$this->stream->eof()) {
                hash_update($context, $this->stream->read(8192));
            }
            $this->stream->rewind();
        } catch (\RuntimeException $exception) {
            throw new StreamException('计算协议流摘要失败', 0, $exception);
        }

        return hash_final($context);
    }
}
