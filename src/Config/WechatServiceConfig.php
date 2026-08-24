<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;
use We\Support\ConfigValue;
use We\Support\CredentialValidator;

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
        public readonly string $componentAppid,
        public readonly string $componentAppSecret,
        public readonly string $componentToken,
        public readonly string $componentEncodingAesKey,
        /** @see WechatPlatformConfig::$storageScope */
        public readonly string $storageScope = '',
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
        CredentialValidator::assertWechatToken($this->componentToken, 'componentToken');
        CredentialValidator::assertEncodingAesKey($this->componentEncodingAesKey, 'componentEncodingAesKey');
    }

    /**
     * 从数组创建微信服务平台（第三方平台）配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            ConfigValue::string($data, ['component_appid'], 'componentAppid', '', WechatException::class),
            ConfigValue::string($data, ['component_appsecret', 'component_app_secret'], 'componentAppSecret', '', WechatException::class),
            ConfigValue::string($data, ['component_token'], 'componentToken', '', WechatException::class),
            ConfigValue::string($data, ['component_encodingaeskey', 'component_encoding_aes_key'], 'componentEncodingAesKey', '', WechatException::class),
            ConfigValue::string($data, ['storage_scope', 'storageScope'], 'storageScope', '', WechatException::class),
        );
    }
}
