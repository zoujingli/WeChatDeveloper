<?php

declare(strict_types=1);

/**
 * SDK 基础异常。
 */

namespace We\Exception;

use RuntimeException;
use Throwable;

/**
 * SDK 基础异常。
 *
 * 通过 context() 暴露平台响应、验签明细等上下文，便于业务侧记录日志和排查问题。
 */
class WechatException extends RuntimeException
{
    /**
     * 创建 SDK 异常并保存可选上下文。
     *
     * @param array<string,mixed> $context
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?Throwable $previous = null,
        private readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取异常附带的平台响应或验签上下文。
     *
     * @return array<string,mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
