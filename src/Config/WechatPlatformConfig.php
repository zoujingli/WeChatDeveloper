<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

final class WechatPlatformConfig implements ConfigInterface
{
    public function __construct(
        public string $appid,
        public string $appSecret,
        public string $token = '',
        public string $encodingAesKey = '',
        /**
         * 业务维度的缓存分桶：Token 键形如 `wechat:app:{appid}:official:access_token:scope:{storageScope}`；
         * 空字符串则仅按 appid 分组。多租户下同一 appid 若需隔离（极少见）可传租户/账号标识。
         */
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
            (string)($data['token'] ?? ''),
            (string)($data['encodingaeskey'] ?? $data['encoding_aes_key'] ?? ''),
            (string)($data['storage_scope'] ?? $data['storageScope'] ?? ''),
        );
    }

    public function validate(): void
    {
        foreach ([
            'appid' => $this->appid,
            'appSecret' => $this->appSecret,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new WechatException($name . ' 不能为空');
            }
        }
    }
}
