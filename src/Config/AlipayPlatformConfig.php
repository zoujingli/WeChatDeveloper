<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;
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
        if (!in_array(strtoupper($this->signType), ['RSA', 'RSA2'], true)) {
            throw new WechatException('支付宝 sign_type 仅支持 RSA 或 RSA2');
        }
        if (!filter_var($this->gateway, FILTER_VALIDATE_URL)) {
            throw new WechatException('支付宝 gateway 必须是有效 URL');
        }
        CredentialValidator::assertPrivateKey($this->privateKey, '支付宝 private_key', true);
        if ($this->alipayPublicKey !== '') {
            CredentialValidator::assertPublicKey($this->alipayPublicKey, '支付宝 alipay_public_key', true);
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
