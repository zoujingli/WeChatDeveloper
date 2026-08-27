<?php

declare(strict_types=1);

namespace We\Common\Provider;

use We\Common\Exception\ConfigurationException;

/** 按平台通道和密钥 ID 读取验签公钥。 */
interface TrustMaterialProviderInterface
{
    /**
     * 返回指定通道和密钥 ID 对应的 PEM 公钥。
     *
     * @throws ConfigurationException 信任材料不存在或不可用
     */
    public function publicKey(string $channel, string $keyId): string;
}
