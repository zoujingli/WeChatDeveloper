<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

final class WechatPaymentConfig implements ConfigInterface
{
    public function __construct(
        public string $appid,
        public string $mchId,
        public string $apiV3Key,
        public string $merchantSerial,
        public string $merchantPrivateKey,
        public string $platformCertificate = '',
        public string $platformPublicKey = '',
        public string $platformSerial = '',
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        foreach ([
            'appid' => $this->appid,
            'mchId' => $this->mchId,
            'apiV3Key' => $this->apiV3Key,
            'merchantSerial' => $this->merchantSerial,
            'merchantPrivateKey' => $this->merchantPrivateKey,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new WechatException($name . ' 不能为空');
            }
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            (string)($data['appid'] ?? ''),
            (string)($data['mch_id'] ?? $data['mchid'] ?? ''),
            (string)($data['api_v3_key'] ?? $data['mch_v3_key'] ?? ''),
            (string)($data['merchant_serial'] ?? $data['cert_serial'] ?? ''),
            (string)($data['merchant_private_key'] ?? $data['cert_private'] ?? ''),
            (string)($data['platform_certificate'] ?? $data['cert_public'] ?? ''),
            (string)($data['platform_public_key'] ?? ''),
            (string)($data['platform_serial'] ?? ''),
        );
    }
}
