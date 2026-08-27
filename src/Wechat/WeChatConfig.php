<?php

declare(strict_types=1);

namespace We\Wechat;

use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Support\ConfigValue;
use We\Wechat\Common\WeChatTokenStrategy;

/** 微信公众号 API 调用配置。 */
final class WeChatConfig
{
    public function __construct(
        public readonly string $appid,
        public readonly string $appSecret,
        /** 同一 `appid` 需要业务隔离时使用的 Token 缓存分区。 */
        public readonly string $storageScope = '',
        public readonly WeChatTokenStrategy $tokenStrategy = WeChatTokenStrategy::Standard,
        public readonly Endpoint $endpoint = new Endpoint('https://api.weixin.qq.com'),
    ) {
        $this->validate();
    }

    /**
     * 从数组创建微信公众号配置，兼容常见下划线字段名。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $strategy = ConfigValue::string($data, ['token_strategy'], 'tokenStrategy', 'standard', ConfigurationException::class);
        try {
            $tokenStrategy = WeChatTokenStrategy::from($strategy);
        } catch (\ValueError $exception) {
            throw new ConfigurationException('`tokenStrategy` 仅支持 `standard` 或 `stable`', 0, $exception);
        }

        return new self(
            ConfigValue::string($data, ['appid'], 'appid', '', ConfigurationException::class),
            ConfigValue::string($data, ['appsecret', 'app_secret'], 'appSecret', '', ConfigurationException::class),
            ConfigValue::string($data, ['storage_scope', 'storageScope'], 'storageScope', '', ConfigurationException::class),
            $tokenStrategy,
            new Endpoint(ConfigValue::string($data, ['endpoint'], 'endpoint', 'https://api.weixin.qq.com', ConfigurationException::class)),
        );
    }

    /** 校验微信公众号 `appid` 与 `appSecret`。 */
    public function validate(): void
    {
        foreach ([
            'appid' => $this->appid,
            'appSecret' => $this->appSecret,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new ConfigurationException('`' . $name . '` 不能为空');
            }
        }
    }
}
