<?php

declare(strict_types=1);

namespace We\Wechat\WxOpen;

/** 读取并保存微信开放平台授权方 Token 的存储接口。 */
interface StoreTokenInterface
{
    /** 返回授权方非空的 `authorizer_refresh_token`。 */
    public function refreshToken(string $authorizerAppid): string;

    /**
     * 保存已完成 Token 字段与有效期校验的授权方刷新响应。
     *
     * @param array<string,mixed> $payload
     */
    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void;
}
