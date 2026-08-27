<?php

declare(strict_types=1);

namespace We\Wechat\WxOpen;

use We\Common\Exception\ConfigurationException;

/** 读取业务系统保存的微信 `component_verify_ticket`。 */
interface ComponentTicketProviderInterface
{
    /**
     * 返回指定第三方平台当前有效且非空的 `component_verify_ticket`。
     *
     * @throws ConfigurationException ticket 不存在或不可用
     */
    public function ticket(string $componentAppid): string;
}
