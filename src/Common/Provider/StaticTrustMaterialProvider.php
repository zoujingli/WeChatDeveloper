<?php

declare(strict_types=1);

namespace We\Common\Provider;

use We\Common\Exception\ConfigurationException;
use We\Common\Support\CredentialValidator;

/** 从内存映射读取平台公钥或证书的信任材料 Provider。 */
final class StaticTrustMaterialProvider implements TrustMaterialProviderInterface
{
    /** @var array<string,array<string,string>> */
    private array $keys = [];

    /**
     * @param array<string,array<string,string>> $keys 平台通道 => 密钥 ID => PEM 公钥或证书
     * @param bool $wrapRawKey 是否为无 PEM 边界的 Base64 公钥补齐边界
     */
    public function __construct(array $keys, bool $wrapRawKey = false)
    {
        foreach ($keys as $channel => $materials) {
            foreach ($materials as $keyId => $material) {
                $this->add($channel, $keyId, $material, $wrapRawKey);
            }
        }
    }

    public function publicKey(string $channel, string $keyId): string
    {
        $key = $this->keys[$channel][$keyId] ?? null;
        if (!is_string($key)) {
            throw new ConfigurationException(
                '未找到平台信任材料',
                context: ['channel' => $channel, 'key_id' => $keyId],
                channel: $channel,
            );
        }

        return $key;
    }

    private function add(string $channel, string $keyId, string $material, bool $wrapRawKey): void
    {
        if (trim($channel) === '' || trim($keyId) === '') {
            throw new ConfigurationException('信任材料通道与密钥 ID 不能为空');
        }
        $normalized = CredentialValidator::normalizePublicKey($material, $wrapRawKey);
        CredentialValidator::assertPublicKey(
            $normalized,
            '信任材料',
            exceptionClass: ConfigurationException::class,
        );
        $this->keys[$channel][$keyId] = $normalized;
    }
}
