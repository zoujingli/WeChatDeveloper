<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

/**
 * @phpstan-consistent-constructor
 */
class AlipayPlatformConfig implements ConfigInterface
{
    public function __construct(
        public string $appid,
        public string $privateKey,
        public string $alipayPublicKey = '',
        public string $gateway = 'https://openapi.alipay.com/gateway.do',
        public string $charset = 'utf-8',
        public string $signType = 'RSA2',
        public string $format = 'JSON',
        public string $version = '1.0',
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->privateKey) === '') {
            throw new WechatException('支付宝 appid 与 private_key 不能为空');
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            (string)($data['appid'] ?? $data['app_id'] ?? ''),
            (string)($data['private_key'] ?? $data['merchant_private_key'] ?? ''),
            (string)($data['alipay_public_key'] ?? ''),
            (string)($data['gateway'] ?? 'https://openapi.alipay.com/gateway.do'),
            (string)($data['charset'] ?? 'utf-8'),
            (string)($data['sign_type'] ?? 'RSA2'),
            (string)($data['format'] ?? 'JSON'),
            (string)($data['version'] ?? '1.0'),
        );
    }
}
