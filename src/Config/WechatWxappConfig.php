<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

final class WechatWxappConfig implements ConfigInterface
{
    public function __construct(
        public string $appid,
        public string $appSecret,
        /** @see WechatPlatformConfig::$storageScope */
        public string $storageScope = '',
    ) {
        $this->validate();
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            (string)($data['appid'] ?? ''),
            (string)($data['appsecret'] ?? $data['app_secret'] ?? ''),
            (string)($data['storage_scope'] ?? $data['storageScope'] ?? ''),
        );
    }

    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->appSecret) === '') {
            throw new WechatException('小程序 appid 与 appSecret 不能为空');
        }
    }
}
