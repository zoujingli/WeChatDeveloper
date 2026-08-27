<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

use We\Common\Exception\SdkException;

/**
 * 将运行前缀、平台通道和逻辑键编码为 PSR-16 安全的缓存键。
 *
 * @internal
 */
final class CacheKey
{
    /**
     * 组合缓存完整键。
     *
     * @param string $prefix 运行环境命名段，用于区分部署、租户或应用
     * @param string $channel 平台通道标识，例如 `wechat.platform`
     * @param string $logicalKey Token 逻辑键
     */
    public static function compose(string $prefix, string $channel, string $logicalKey): string
    {
        $prefix = self::normalizeSegment($prefix);
        $channel = self::normalizeSegment($channel);
        $logical = trim($logicalKey);
        if ($prefix === '') {
            throw new SdkException('缓存键通用前缀不能为空');
        }
        if ($channel === '') {
            throw new SdkException('缓存键通道段不能为空');
        }
        if ($logical === '') {
            throw new SdkException('缓存键逻辑段不能为空');
        }

        return implode('.', array_map(self::encodeSegment(...), [$prefix, $channel, $logical]));
    }

    /**
     * 编码单个键段；额外编码点号，确保它只承担段分隔符语义。
     */
    private static function encodeSegment(string $segment): string
    {
        return str_replace('.', '%2E', rawurlencode($segment));
    }

    /**
     * 规范化缓存键命名段并去除两侧多余冒号。
     */
    private static function normalizeSegment(string $segment): string
    {
        $segment = trim($segment);
        if ($segment === '') {
            return '';
        }

        return trim($segment, ':');
    }
}
