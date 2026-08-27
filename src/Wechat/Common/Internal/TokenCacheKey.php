<?php

declare(strict_types=1);

namespace We\Wechat\Common\Internal;

/**
 * 微信 Token 缓存逻辑键统一按 `appid` 分段，便于按应用扫描和清理。
 *
 * 约定前缀：`wechat:app:{appid}:...`；配置包含 `storageScope` 时追加 `:scope:{值}`。
 * 经各通道写入存储时，作为第三段与通用前缀、平台段经 {@see CacheKey::compose} 拼成固定三段键。
 *
 * @internal
 */
final class TokenCacheKey
{
    /**
     * 微信公众号 `client_credential` access Token。
     * 例：`wechat:app:wxabcd:platform:access_token` 或带 scope 后缀。
     */
    public static function weChatAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':platform:access_token', $storageScope);
    }

    /**
     * 微信小程序 `client_credential` access Token。
     */
    public static function wxAppAccessToken(string $appid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $appid . ':wxapp:access_token', $storageScope);
    }

    /**
     * 微信开放平台 `component_access_token`，按 `component_appid` 分组。
     */
    public static function wechatOpenComponentAccessToken(string $componentAppid, string $storageScope = ''): string
    {
        return self::appendScope('wechat:app:' . $componentAppid . ':service:component_access_token', $storageScope);
    }

    /**
     * 微信开放平台 `authorizer_access_token`，按 `component_appid` 与授权方分组。
     */
    public static function wechatOpenAuthorizerAccessToken(string $componentAppid, string $authorizerAppid, string $storageScope = ''): string
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
