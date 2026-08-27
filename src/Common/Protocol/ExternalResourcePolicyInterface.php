<?php

declare(strict_types=1);

namespace We\Common\Protocol;

use We\Common\Exception\InvalidCallException;
use We\Common\Resource;

/** 校验派生资源主机和 IP 地址的安全策略接口。 */
interface ExternalResourcePolicyInterface
{
    /**
     * 根据来源通道在派生资源发送前校验网络目标。
     *
     * @throws InvalidCallException 资源不满足部署侧网络策略
     */
    public function assertAllowed(string $channel, Resource $resource): void;
}
