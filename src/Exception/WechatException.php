<?php

declare(strict_types=1);

namespace We\Exception;

use RuntimeException;
use Throwable;

class WechatException extends RuntimeException
{
    /**
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
     * @return array<string,mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
