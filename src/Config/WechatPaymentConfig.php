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
 * 微信支付 APIv3 商户配置。
 *
 * 商户号、商户 API 证书序列号和商户私钥用于生成 APIv3 请求签名；APIv3 密钥用于回调资源解密；微信支付平台证书或平台公钥用于通知验签。
 */
final class WechatPaymentConfig implements ConfigInterface
{
    /**
     * 创建微信支付 APIv3 配置并执行必填项校验。
     */
    public function __construct(
        public string $appid,
        public string $mchId,
        public string $apiV3Key,
        public string $merchantSerial,
        public string $merchantPrivateKey,
        /** 微信支付平台证书 PEM；未配置 platformPublicKey 时用于回调验签。 */
        public string $platformCertificate = '',
        /** 微信支付平台公钥 PEM，优先用于回调验签。 */
        public string $platformPublicKey = '',
        /** 微信支付平台证书/公钥序列号；非空时会校验回调头 Wechatpay-Serial。 */
        public string $platformSerial = '',
    ) {
        $this->validate();
    }

    /**
     * 校验微信支付 appid、商户号、APIv3 密钥、商户证书序列号和商户私钥。
     */
    public function validate(): void
    {
        foreach ([
            'appid' => $this->appid,
            'mchId' => $this->mchId,
            'apiV3Key' => $this->apiV3Key,
            'merchantSerial' => $this->merchantSerial,
            'merchantPrivateKey' => $this->merchantPrivateKey,
        ] as $name => $value) {
            if (trim($value) === '') {
                throw new WechatException($name . ' 不能为空');
            }
        }
        CredentialValidator::assertApiV3Key($this->apiV3Key);
        CredentialValidator::assertPrivateKey($this->merchantPrivateKey, 'merchantPrivateKey');
        if ($this->platformCertificate !== '') {
            CredentialValidator::assertPublicKey($this->platformCertificate, 'platformCertificate');
        }
        if ($this->platformPublicKey !== '') {
            CredentialValidator::assertPublicKey($this->platformPublicKey, 'platformPublicKey');
        }
    }

    /**
     * 从数组创建微信支付 APIv3 商户配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            (string)($data['appid'] ?? ''),
            (string)($data['mch_id'] ?? $data['mchid'] ?? ''),
            (string)($data['api_v3_key'] ?? $data['mch_v3_key'] ?? ''),
            (string)($data['merchant_serial'] ?? $data['cert_serial'] ?? ''),
            (string)($data['merchant_private_key'] ?? $data['cert_private'] ?? ''),
            (string)($data['platform_certificate'] ?? $data['cert_public'] ?? ''),
            (string)($data['platform_public_key'] ?? ''),
            (string)($data['platform_serial'] ?? ''),
        );
    }
}
