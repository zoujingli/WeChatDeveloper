<?php

declare(strict_types=1);

namespace We\Alipay\Common\Internal;

use We\Alipay\Common\TokenKind;
use We\Alipay\Common\TokenProviderInterface;
use We\Common\Exception\ConfigurationException;

/**
 * 未配置支付宝身份 Token Provider 时使用的失败关闭实现。
 *
 * @internal
 */
final class NullTokenProvider implements TokenProviderInterface
{
    public function token(TokenKind $kind, string $credentialId): string
    {
        throw new ConfigurationException(
            '当前通道未配置调用身份 Token Provider',
            context: ['kind' => $kind->value, 'credential_id' => $credentialId],
        );
    }
}
