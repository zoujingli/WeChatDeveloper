<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Support;

use We\Exception\WechatException;

/**
 * SDK 缓存完整键生成器，固定为 `{cacheKeyPrefix}:{platformChannel}:{logicalKey}` 三段，便于按项目、通道和逻辑键隔离。
 */
final class CacheKey
{
    /**
     * 组合缓存完整键。
     *
     * @param string $prefix 根 Client 通用命名段，不得为空，用于区分部署、租户或应用
     * @param string $channel 通道段，如 `wechat.platform`，必须与 Client 通道标识一致。
     * @param string $logicalKey 业务逻辑键，如 TokenCacheKey 生成的 access_token 键
     */
    public static function compose(string $prefix, string $channel, string $logicalKey): string
    {
        $prefix = self::normalizeSegment($prefix);
        $channel = self::normalizeSegment($channel);
        $logical = trim($logicalKey);
        if ($prefix === '') {
            throw new WechatException('缓存键通用前缀不能为空');
        }
        if ($channel === '') {
            throw new WechatException('缓存键通道段不能为空');
        }
        if ($logical === '') {
            throw new WechatException('缓存键逻辑段不能为空');
        }

        return $prefix . ':' . $channel . ':' . $logical;
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
