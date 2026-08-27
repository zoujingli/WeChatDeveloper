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

/** 支付宝支付 v2 AOP Gateway 通道配置。 */
final class AliPayConfig
{
    public function __construct(
        public readonly string $appid,
        public readonly SigningKeyProviderInterface $signer,
        public readonly TrustMaterialProviderInterface $trust,
        public readonly string $defaultTrustKeyId = 'default',
        public readonly string $charset = 'utf-8',
        public readonly string $signType = 'RSA2',
        public readonly string $format = 'JSON',
        public readonly string $version = '1.0',
        public readonly string $appCertificateSerial = '',
        public readonly string $alipayRootCertificateSerial = '',
        public readonly Endpoint $endpoint = new Endpoint('https://openapi.alipay.com/gateway.do'),
    ) {
        $this->validate();
    }

    /**
     * 从数组创建使用本地 PEM 签名和静态平台信任材料的 Gateway 配置。
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
                'alipay.gateway' => [
                    $trustKeyId => ConfigValue::string($data, ['alipay_public_key'], 'alipayPublicKey', '', ConfigurationException::class),
                ],
            ], true),
            $trustKeyId,
            ConfigValue::string($data, ['charset'], 'charset', 'utf-8', ConfigurationException::class),
            ConfigValue::string($data, ['sign_type'], 'signType', 'RSA2', ConfigurationException::class),
            ConfigValue::string($data, ['format'], 'format', 'JSON', ConfigurationException::class),
            ConfigValue::string($data, ['version'], 'version', '1.0', ConfigurationException::class),
            ConfigValue::string($data, ['app_cert_sn'], 'appCertificateSerial', '', ConfigurationException::class),
            ConfigValue::string($data, ['alipay_root_cert_sn'], 'alipayRootCertificateSerial', '', ConfigurationException::class),
            new Endpoint(ConfigValue::string($data, ['gateway', 'endpoint'], 'endpoint', 'https://openapi.alipay.com/gateway.do', ConfigurationException::class)),
        );
    }

    /** 校验支付宝应用身份、签名类型和响应格式。 */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->defaultTrustKeyId) === '') {
            throw new ConfigurationException('支付宝 `appid` 与默认信任材料 ID 不能为空');
        }
        if (
            trim($this->charset) === ''
            || trim($this->version) === ''
            || preg_match('/[\x00-\x1F\x7F]/', $this->charset . $this->version) === 1
        ) {
            throw new ConfigurationException('支付宝 v2 `charset` 与 `version` 必须是非空协议值');
        }
        if (!in_array(strtoupper($this->signType), ['RSA', 'RSA2'], true)) {
            throw new ConfigurationException('支付宝 v2 `signType` 仅支持 RSA 或 RSA2');
        }
        if (!in_array(strtoupper($this->format), ['JSON', 'XML'], true)) {
            throw new ConfigurationException('支付宝 v2 `format` 仅支持 JSON 或 XML');
        }
    }
}
