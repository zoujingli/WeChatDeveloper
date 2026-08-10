<?php

declare(strict_types=1);

namespace We\Exception;

/**
 * SDK 基础异常。
 *
 * 通过 context() 暴露平台响应、验签明细等上下文，便于业务侧记录日志和排查问题。
 */
class SdkException extends \RuntimeException
{
    /**
     * @param array<string,mixed> $context
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string,mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
