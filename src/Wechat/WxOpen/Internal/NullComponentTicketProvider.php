<?php

declare(strict_types=1);

namespace We\Wechat\WxOpen\Internal;

use We\Common\Exception\ConfigurationException;
use We\Wechat\WxOpen\ComponentTicketProviderInterface;

/**
 * 未配置 `component_verify_ticket` Provider 时使用的失败关闭实现。
 *
 * @internal
 */
final class NullComponentTicketProvider implements ComponentTicketProviderInterface
{
    public function ticket(string $componentAppid): string
    {
        throw new ConfigurationException('当前通道未配置 `component_verify_ticket` Provider');
    }
}
