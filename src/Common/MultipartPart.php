<?php

declare(strict_types=1);

namespace We\Common;

use Psr\Http\Message\StreamInterface;
use We\Common\Exception\InvalidCallException;

/** `multipart` 请求体中的普通字段或文件部件；流内容从当前位置读取。 */
final class MultipartPart
{
    /** @param array<string,string> $headers */
    public function __construct(
        public readonly string $name,
        public readonly StreamInterface|string $contents,
        public readonly ?string $filename = null,
        public readonly ?string $mediaType = null,
        public readonly array $headers = [],
    ) {
        if (trim($this->name) === '' || preg_match('/[\r\n]/', $this->name) === 1) {
            throw new InvalidCallException('`multipart` 部件名称无效');
        }
        if ($this->filename !== null && preg_match('/[\r\n]/', $this->filename) === 1) {
            throw new InvalidCallException('`multipart` 文件名无效');
        }
        if ($this->mediaType !== null && (trim($this->mediaType) === '' || preg_match('/[\r\n]/', $this->mediaType) === 1)) {
            throw new InvalidCallException('`multipart` 媒体类型无效');
        }
        if ($this->contents instanceof StreamInterface && !$this->contents->isReadable()) {
            throw new InvalidCallException('`multipart` 内容流不可读');
        }
        foreach ($this->headers as $name => $value) {
            $name = (string)$name;
            if (!is_string($value)
                || preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/D", $name) !== 1
                || preg_match('/[\r\n]/', $value) === 1
            ) {
                throw new InvalidCallException('`multipart` 请求头无效');
            }
        }
    }
}
