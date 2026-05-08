<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Contract;

/**
 * 微信服务平台授权方 Token 存储契约；SDK 读取 authorizer_refresh_token，并在刷新后回写授权方 Token 数据。
 */
interface StoreTokenInterface
{
    /**
     * 获取授权方账号的 authorizer_refresh_token。
     */
    public function refreshToken(string $authorizerAppid): string;

    /**
     * 授权方 authorizer_access_token 刷新后回写业务存储。
     *
     * @param array<string,mixed> $payload
     */
    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void;
}
