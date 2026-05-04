<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

final class WechatServiceConfig implements ConfigInterface
{
    public function __construct(
        public string $componentAppid,
        public string $componentAppSecret,
        public string $componentToken,
        public string $componentEncodingAesKey,
        /** @see WechatPlatformConfig::$storageScope */
        public string $storageScope = '',
    ) {
        $this->validate();
    }

    public function validate(): void
    {
        foreach ([
            'componentAppid' => $this->componentAppid,
            'componentAppSecret' => $this->componentAppSecret,
            'componentToken' => $this->componentToken,
            'componentEncodingAesKey' => $this->componentEncodingAesKey,
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
            (string)($data['component_appid'] ?? ''),
            (string)($data['component_appsecret'] ?? $data['component_app_secret'] ?? ''),
            (string)($data['component_token'] ?? ''),
            (string)($data['component_encodingaeskey'] ?? $data['component_encoding_aes_key'] ?? ''),
            (string)($data['storage_scope'] ?? $data['storageScope'] ?? ''),
        );
    }
}
