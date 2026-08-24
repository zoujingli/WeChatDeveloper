<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\AlipayException;
use We\Support\ConfigValue;
use We\Support\CredentialValidator;

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
        public readonly string $appid,
        public readonly string $privateKey,
        public readonly string $alipayPublicKey = '',
        public readonly string $gateway = 'https://openapi.alipay.com/gateway.do',
        public readonly string $charset = 'utf-8',
        public readonly string $signType = 'RSA2',
        public readonly string $format = 'JSON',
        public readonly string $version = '1.0',
    ) {
        $this->validate();
    }

    /**
     * 校验支付宝 app_id 与应用私钥。
     */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->privateKey) === '' || trim($this->alipayPublicKey) === '') {
            throw new AlipayException('支付宝 appid、private_key 与 alipay_public_key 不能为空');
        }
        if (!in_array(strtoupper($this->signType), ['RSA', 'RSA2'], true)) {
            throw new AlipayException('支付宝 sign_type 仅支持 RSA 或 RSA2');
        }
        if (!filter_var($this->gateway, FILTER_VALIDATE_URL)) {
            throw new AlipayException('支付宝 gateway 必须是有效 URL');
        }
        CredentialValidator::assertPrivateKey($this->privateKey, '支付宝 private_key', true, AlipayException::class);
        CredentialValidator::assertPublicKey($this->alipayPublicKey, '支付宝 alipay_public_key', true, AlipayException::class);
    }

    /**
     * 从数组创建支付宝开放平台配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static(
            ConfigValue::string($data, ['appid', 'app_id'], 'appid', '', AlipayException::class),
            ConfigValue::string($data, ['private_key', 'merchant_private_key'], 'private_key', '', AlipayException::class),
            ConfigValue::string($data, ['alipay_public_key'], 'alipay_public_key', '', AlipayException::class),
            ConfigValue::string($data, ['gateway'], 'gateway', 'https://openapi.alipay.com/gateway.do', AlipayException::class),
            ConfigValue::string($data, ['charset'], 'charset', 'utf-8', AlipayException::class),
            ConfigValue::string($data, ['sign_type'], 'sign_type', 'RSA2', AlipayException::class),
            ConfigValue::string($data, ['format'], 'format', 'JSON', AlipayException::class),
            ConfigValue::string($data, ['version'], 'version', '1.0', AlipayException::class),
        );
    }
}
