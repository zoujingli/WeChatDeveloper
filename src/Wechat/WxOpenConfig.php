<?php

declare(strict_types=1);

namespace We\Wechat;

use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Support\ConfigValue;

/** 微信开放平台第三方平台通道配置。 */
final class WxOpenConfig
{
    public function __construct(
        public readonly string $componentAppid,
        public readonly string $componentAppSecret,
        /** 同一 `componentAppid` 需要业务隔离时使用的 Token 缓存分区。 */
        public readonly string $storageScope = '',
        public readonly Endpoint $endpoint = new Endpoint('https://api.weixin.qq.com'),
    ) {
        $this->validate();
    }

    /** 校验微信开放平台 `componentAppid` 与 `componentAppSecret`。 */
    public function validate(): void
    {
        foreach ([
            'componentAppid' => $this->componentAppid,
            'componentAppSecret' => $this->componentAppSecret,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new ConfigurationException('`' . $name . '` 不能为空');
            }
        }
    }

    /**
     * 从数组创建微信开放平台配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            ConfigValue::string($data, ['component_appid'], 'componentAppid', '', ConfigurationException::class),
            ConfigValue::string($data, ['component_appsecret', 'component_app_secret'], 'componentAppSecret', '', ConfigurationException::class),
            ConfigValue::string($data, ['storage_scope', 'storageScope'], 'storageScope', '', ConfigurationException::class),
            new Endpoint(ConfigValue::string($data, ['endpoint'], 'endpoint', 'https://api.weixin.qq.com', ConfigurationException::class)),
        );
    }
}
