<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;
use We\Support\ConfigValue;

/**
 * 微信小程序基础配置。
 *
 * appid/appSecret 用于获取小程序接口调用凭据 access_token；storageScope 用于隔离不同业务上下文下的凭据缓存。
 */
final class WechatWxappConfig implements ConfigInterface
{
    /**
     * 创建微信小程序配置并执行必填项校验。
     */
    public function __construct(
        public readonly string $appid,
        public readonly string $appSecret,
        /** @see WechatPlatformConfig::$storageScope */
        public readonly string $storageScope = '',
    ) {
        $this->validate();
    }

    /**
     * 从数组创建微信小程序配置，兼容常见下划线字段名。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            ConfigValue::string($data, ['appid'], 'appid', '', WechatException::class),
            ConfigValue::string($data, ['appsecret', 'app_secret'], 'appSecret', '', WechatException::class),
            ConfigValue::string($data, ['storage_scope', 'storageScope'], 'storageScope', '', WechatException::class),
        );
    }

    /**
     * 校验小程序 appid 与 appSecret。
     */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->appSecret) === '') {
            throw new WechatException('小程序 appid 与 appSecret 不能为空');
        }
    }
}
