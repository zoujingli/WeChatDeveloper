<?php

declare(strict_types=1);

namespace We\Wechat\WxOpen;

use We\Common\Exception\ConfigurationException;

/** 从内存映射读取 `component_verify_ticket` 的 Provider。 */
final class StaticComponentTicketProvider implements ComponentTicketProviderInterface
{
    /** @param array<string,string> $tickets */
    public function __construct(private readonly array $tickets) {}

    public function ticket(string $componentAppid): string
    {
        $ticket = $this->tickets[$componentAppid] ?? null;
        if (!is_string($ticket) || trim($ticket) === '') {
            throw new ConfigurationException('未找到 `component_verify_ticket`');
        }

        return $ticket;
    }
}
