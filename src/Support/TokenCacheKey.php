<?php

declare(strict_types=1);

namespace We\Support;

/**
 * Token 缓存键统一按「微信侧 appid 优先」分段，便于 Redis 等按前缀扫描、按应用清理。
 *
 * 约定前缀：`wechat:app:{appid}:...`；若 Config 带 `storageScope`（业务隔离），追加 `:scope:{值}`。
 * 经根 {@see \We\Client} 与各通道写入存储时，作为第三段与通用前缀、平台段经 {@see CacheKey::compose} 拼成固定三段键。
 */
final class TokenCacheKey
{
    /**
     * 公众号 client_credential access_token
     * 例：`wechat:app:wxabcd:official:access_token` 或带 scope 后缀。
     */
    public static function wechatOfficialAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':official:access_token', $storageScope);
    }

    /**
     * 小程序 client_credential access_token
     */
    public static function wechatMiniAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':mini:access_token', $storageScope);
    }

    /**
     * 开放平台第三方 component_access_token（按 component_appid 分组）
     */
    public static function wechatOpenComponentAccessToken(string $componentAppid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $componentAppid . ':open:component_access_token', $storageScope);
    }

    /**
     * 开放平台代授权方 authorizer_access_token（挂在 component_appid 下，避免键平面冲突）
     */
    public static function wechatOpenAuthorizerAccessToken(string $componentAppid, string $authorizerAppid, string $storageScope = ''): string
    {
        return self::appendScope(
            'wechat:app:' . $componentAppid . ':open:authorizer:' . $authorizerAppid . ':access_token',
            $storageScope,
        );
    }

    private static function appendScope(string $base, string $storageScope): string
    {
        $s = trim($storageScope);

        return $s === '' ? $base : $base . ':scope:' . $s;
    }
}
