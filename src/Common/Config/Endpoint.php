<?php

declare(strict_types=1);

namespace We\Common\Config;

use We\Common\Exception\ConfigurationException;

/** 平台通道使用的生产、沙箱或自定义 HTTPS 端点。 */
final class Endpoint
{
    /** 创建不包含用户信息的 HTTPS 端点。 */
    public function __construct(public readonly string $baseUri)
    {
        $parts = parse_url($this->baseUri);
        if (
            filter_var($this->baseUri, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) != 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new ConfigurationException('端点配置必须使用不含用户信息的 HTTPS URL');
        }
    }
}
