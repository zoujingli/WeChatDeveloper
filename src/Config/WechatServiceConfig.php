<?php

declare(strict_types=1);

/**
 * 微信服务平台（第三方平台）配置对象。
 */

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

/**
 * 微信服务平台（第三方平台）配置。
 *
 * componentAppid/componentAppSecret 用于获取第三方平台 component_access_token；componentToken/componentEncodingAesKey 用于授权事件接收 URL 的签名校验与安全模式消息解密。
 */
final class WechatServiceConfig implements ConfigInterface
{
    /**
     * 创建微信服务平台（第三方平台）配置并执行必填项校验。
     */
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

    /**
     * 校验第三方平台 component_appid、secret、Token 与 EncodingAESKey。
     */
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
     * 从数组创建微信服务平台（第三方平台）配置。
     *
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
