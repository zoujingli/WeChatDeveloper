<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Support;

use We\Client;

/**
 * 微信接口调用凭据缓存逻辑键统一按「微信侧 appid 优先」分段，便于 Redis 等存储按应用前缀扫描和清理。
 *
 * 约定前缀：`wechat:app:{appid}:...`；若 Config 带 `storageScope`（业务隔离），追加 `:scope:{值}`。
 * 经根 {@see Client} 与各通道写入存储时，作为第三段与通用前缀、平台段经 {@see CacheKey::compose} 拼成固定三段键。
 */
final class TokenCacheKey
{
    /**
     * 微信公众平台 client_credential access_token。
     * 例：`wechat:app:wxabcd:platform:access_token` 或带 scope 后缀。
     */
    public static function wechatPlatformAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':platform:access_token', $storageScope);
    }

    /**
     * 小程序 client_credential access_token。
     */
    public static function wechatWxappAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':wxapp:access_token', $storageScope);
    }

    /**
     * 微信服务平台（第三方平台） component_access_token（按 component_appid 分组）。
     */
    public static function wechatServiceComponentAccessToken(string $componentAppid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $componentAppid . ':service:component_access_token', $storageScope);
    }

    /**
     * 微信服务平台代授权方 authorizer_access_token（挂在 component_appid 下，避免键平面冲突）。
     */
    public static function wechatServiceAuthorizerAccessToken(string $componentAppid, string $authorizerAppid, string $storageScope = ''): string
    {
        return self::appendScope(
            'wechat:app:' . $componentAppid . ':service:authorizer:' . $authorizerAppid . ':access_token',
            $storageScope,
        );
    }

    /**
     * 按需追加 storageScope，隔离同一 appid 在不同业务上下文中的凭据缓存。
     */
    private static function appendScope(string $base, string $storageScope): string
    {
        $s = trim($storageScope);

        return $s === '' ? $base : $base . ':scope:' . $s;
    }
}
