<?php

declare(strict_types=1);

namespace We\Alipay\Common;

use We\Common\Exception\ConfigurationException;

/** 从内存映射读取支付宝调用身份 Token 的 Provider。 */
final class StaticTokenProvider implements TokenProviderInterface
{
    /** @param array<string,array<string,string>> $tokens Token 类型值 => 凭证 ID => Token */
    public function __construct(private readonly array $tokens) {}

    public function token(TokenKind $kind, string $credentialId): string
    {
        $token = $this->tokens[$kind->value][$credentialId] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new ConfigurationException(
                '未找到调用身份 Token',
                context: ['kind' => $kind->value, 'credential_id' => $credentialId],
            );
        }

        return $token;
    }
}
