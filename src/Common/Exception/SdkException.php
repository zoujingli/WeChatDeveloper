<?php

declare(strict_types=1);

namespace We\Common\Exception;

/** 提供脱敏诊断上下文的 SDK 基础异常。 */
class SdkException extends \RuntimeException
{
    /**
     * @param array<string,mixed> $context
     */
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $context = [],
        private readonly ?string $channel = null,
        private readonly ?string $requestId = null,
        private readonly int|string|null $platformCode = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string,mixed> */
    public function context(): array
    {
        return $this->context;
    }

    /** 返回发生故障的平台通道。 */
    public function channel(): ?string
    {
        return $this->channel;
    }

    /** 返回平台响应携带的请求标识。 */
    public function requestId(): ?string
    {
        return $this->requestId;
    }

    /** 返回平台业务错误码。 */
    public function platformCode(): int|string|null
    {
        return $this->platformCode;
    }
}
