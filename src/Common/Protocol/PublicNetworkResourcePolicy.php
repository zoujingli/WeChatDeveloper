<?php

declare(strict_types=1);

namespace We\Common\Protocol;

use We\Common\Exception\InvalidCallException;
use We\Common\Resource;

/** 拒绝使用字面私网、保留或回环 IP 地址的默认资源策略。 */
final class PublicNetworkResourcePolicy implements ExternalResourcePolicyInterface
{
    public function assertAllowed(string $channel, Resource $resource): void
    {
        $host = parse_url($resource->url, PHP_URL_HOST);
        if (!is_string($host) || $host === '') {
            throw new InvalidCallException('派生资源主机无效', channel: $channel);
        }
        if (
            filter_var($host, FILTER_VALIDATE_IP) !== false
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
        ) {
            throw new InvalidCallException('派生资源不能使用字面私网、保留或回环 IP 地址', channel: $channel);
        }
    }
}
