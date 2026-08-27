<?php

declare(strict_types=1);

namespace We\Alipay;

use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\SigningKeyProviderInterface;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Common\Provider\TrustMaterialProviderInterface;
use We\Common\Support\ConfigValue;

/** 支付宝 REST v3 通道配置。 */
final class AliRestConfig
{
    public function __construct(
        public readonly string $appid,
        public readonly SigningKeyProviderInterface $signer,
        public readonly TrustMaterialProviderInterface $trust,
        public readonly string $defaultTrustKeyId = 'default',
        public readonly string $appCertificateSerial = '',
        public readonly Endpoint $endpoint = new Endpoint('https://openapi.alipay.com'),
    ) {
        $this->validate();
    }

    /**
     * 从数组创建使用本地 PEM 签名和静态平台信任材料的 REST 配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $trustKeyId = ConfigValue::string($data, ['alipay_cert_sn', 'trust_key_id'], 'trustKeyId', 'default', ConfigurationException::class);

        return new self(
            ConfigValue::string($data, ['appid', 'app_id'], 'appid', '', ConfigurationException::class),
            new PemSigningKeyProvider(
                ConfigValue::string($data, ['app_cert_sn', 'signing_key_id'], 'signingKeyId', 'application', ConfigurationException::class),
                ConfigValue::string($data, ['private_key', 'merchant_private_key'], 'privateKey', '', ConfigurationException::class),
                true,
            ),
            new StaticTrustMaterialProvider([
                'alipay.rest' => [
                    $trustKeyId => ConfigValue::string($data, ['alipay_public_key'], 'alipayPublicKey', '', ConfigurationException::class),
                ],
            ], true),
            $trustKeyId,
            ConfigValue::string($data, ['app_cert_sn'], 'appCertificateSerial', '', ConfigurationException::class),
            new Endpoint(ConfigValue::string($data, ['endpoint'], 'endpoint', 'https://openapi.alipay.com', ConfigurationException::class)),
        );
    }

    /** 校验支付宝应用身份和默认信任材料 ID。 */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->defaultTrustKeyId) === '') {
            throw new ConfigurationException('支付宝 v3 `appid` 与默认信任材料 ID 不能为空');
        }
        if (preg_match('/[\x00-\x20\x7F,=]/', $this->appid . $this->appCertificateSerial) === 1) {
            throw new ConfigurationException('支付宝 v3 应用身份协议值无效');
        }
    }
}
