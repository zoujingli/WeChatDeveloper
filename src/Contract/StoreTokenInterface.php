<?php

declare(strict_types=1);

namespace We\Contract;

/**
 * 开放平台授权方 Token 存储契约；SDK 只读取 refresh token 并回写刷新结果。
 */
interface StoreTokenInterface
{
    /**
     * 获取授权账号 refresh token。
     */
    public function refreshToken(string $authorizerAppid): string;

    /**
     * 授权账号 token 刷新后回写业务存储，避免 SDK 持有请求态数据。
     *
     * @param array<string,mixed> $payload
     */
    public function saveAuthorizerToken(string $authorizerAppid, array $payload): void;
}
