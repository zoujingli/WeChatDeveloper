<?php

declare(strict_types=1);

/**
 * 支付宝开放平台配置对象。
 */

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;

/**
 * 支付宝开放平台基础配置。
 *
 * 应用私钥用于支付宝开放平台网关请求签名；支付宝公钥用于同步响应和异步通知验签。
 *
 * @phpstan-consistent-constructor
 */
class AlipayPlatformConfig implements ConfigInterface
{
    /**
     * 创建支付宝开放平台配置并执行必填项校验。
     */
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

    /**
     * 校验支付宝 app_id 与应用私钥。
     */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->privateKey) === '') {
            throw new WechatException('支付宝 appid 与 private_key 不能为空');
        }
    }

    /**
     * 从数组创建支付宝开放平台配置。
     *
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
