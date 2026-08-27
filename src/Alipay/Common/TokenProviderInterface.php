<?php

declare(strict_types=1);

namespace We\Alipay\Common;

use We\Common\Exception\ConfigurationException;

/** 按身份类型和凭证 ID 读取当前有效 Token。 */
interface TokenProviderInterface
{
    /**
     * 返回指定调用身份当前有效且非空的 Token。
     *
     * Provider 负责必要的刷新生命周期，SDK 只在发送前读取结果。
     *
     * @throws ConfigurationException Token 不存在或不可用
     */
    public function token(TokenKind $kind, string $credentialId): string;
}
