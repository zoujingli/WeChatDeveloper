<?php

declare(strict_types=1);

namespace We\Common\Config;

use We\Common\Exception\ConfigurationException;

/** 平台通道使用的生产、沙箱或自定义 HTTPS 端点。 */
final class Endpoint
{
    public readonly string $baseUri;

    /** 创建不包含用户信息的 HTTPS 端点。 */
    public function __construct(string $baseUri)
    {
        $parts = parse_url($baseUri);
        $invalidPath = false;
        if (is_array($parts)) {
            $path = rawurldecode((string)($parts['path'] ?? ''));
            $invalidPath = preg_match('/[\\\\\x00-\x1F\x7F]/', $path) === 1;
            foreach (explode('/', $path) as $segment) {
                if ($segment === '.' || $segment === '..') {
                    $invalidPath = true;
                    break;
                }
            }
        }
        if (
            filter_var($baseUri, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) != 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || $invalidPath
        ) {
            throw new ConfigurationException('端点配置必须使用不含用户信息、查询参数、片段和路径越级段的 HTTPS URL');
        }
        $this->baseUri = rtrim($baseUri, '/');
    }
}
