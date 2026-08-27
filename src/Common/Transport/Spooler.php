<?php

declare(strict_types=1);

namespace We\Common\Transport;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;
use We\Common\Exception\ConfigurationException;
use We\Common\Exception\StreamException;

/**
 * 将尚未完成协议校验的响应流写入受限临时文件，并在验证后复制到目标流。
 *
 * @internal
 */
final class Spooler
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new ConfigurationException('无法创建协议流临时目录');
        }
        if (!is_writable($this->directory)) {
            throw new ConfigurationException('协议流临时目录不可写');
        }
    }

    public function spool(StreamInterface $source, int $limit): Spool
    {
        $path = tempnam($this->directory, 'we-sdk-');
        if (!is_string($path)) {
            throw new StreamException('无法创建协议流临时文件');
        }
        @chmod($path, 0600);
        $resource = @fopen($path, 'w+b');
        if (!is_resource($resource)) {
            @unlink($path);
            throw new StreamException('无法打开协议流临时文件');
        }

        $bytes = 0;
        try {
            while (!$source->eof()) {
                $chunk = $source->read(8192);
                /** @phpstan-ignore-next-line 第三方 StreamInterface 实现可能在 EOF 前返回空串。 */
                if ($chunk === '' && !$source->eof()) {
                    throw new StreamException('读取平台响应流失败');
                }
                $bytes += strlen($chunk);
                if ($bytes > $limit) {
                    throw new StreamException('平台响应超过暂存字节上限');
                }
                if ($chunk !== '' && fwrite($resource, $chunk) !== strlen($chunk)) {
                    throw new StreamException('写入协议流临时文件失败');
                }
            }
            rewind($resource);
        } catch (\Throwable $exception) {
            fclose($resource);
            @unlink($path);
            if ($exception instanceof StreamException) {
                throw $exception;
            }
            throw new StreamException('平台响应流落盘失败', 0, $exception);
        }

        $base = Utils::streamFor($resource);
        $closed = false;
        $stream = FnStream::decorate($base, [
            'close' => static function () use ($base, $path, &$closed): void {
                if ($closed) {
                    return;
                }
                $closed = true;
                $base->close();
                @unlink($path);
            },
            'detach' => static function () use ($base, $path, &$closed): mixed {
                $closed = true;
                $detached = $base->detach();
                @unlink($path);

                return $detached;
            },
        ]);

        return new Spool($stream, $bytes);
    }

    public function copy(Spool $spool, StreamInterface $destination): int
    {
        if (!$destination->isWritable()) {
            throw new StreamException('调用方目标流不可写');
        }
        try {
            $spool->stream->rewind();
            $bytes = 0;
            while (!$spool->stream->eof()) {
                $chunk = $spool->stream->read(8192);
                if ($chunk === '') {
                    continue;
                }
                $written = $destination->write($chunk);
                if ($written != strlen($chunk)) {
                    throw new StreamException('写入调用方目标流失败');
                }
                $bytes += $written;
            }
        } catch (\RuntimeException $exception) {
            throw new StreamException('复制平台响应流失败', 0, $exception);
        }

        return $bytes;
    }
}
