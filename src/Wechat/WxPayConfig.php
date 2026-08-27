<?php

declare(strict_types=1);

namespace We\Wechat;

use We\Common\Config\Endpoint;
use We\Common\Exception\ConfigurationException;
use We\Common\Provider\PemSigningKeyProvider;
use We\Common\Provider\SigningKeyProviderInterface;
use We\Common\Provider\StaticTrustMaterialProvider;
use We\Common\Provider\TrustMaterialProviderInterface;
use We\Common\Support\ConfigValue;

/** 微信支付 APIv3 通道配置。 */
final class WxPayConfig
{
    public function __construct(
        public readonly string $appid,
        public readonly string $mchId,
        public readonly SigningKeyProviderInterface $merchantSigner,
        public readonly TrustMaterialProviderInterface $platformTrust,
        public readonly Endpoint $endpoint = new Endpoint('https://api.mch.weixin.qq.com'),
    ) {
        $this->validate();
    }

    /**
     * 从数组创建使用本地 PEM 签名和静态平台信任材料的微信支付配置。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $serial = ConfigValue::string($data, ['merchant_serial', 'cert_serial'], 'merchantSerial', '', ConfigurationException::class);
        $platformSerial = ConfigValue::string($data, ['platform_serial'], 'platformSerial', '', ConfigurationException::class);
        $platformMaterial = ConfigValue::string(
            $data,
            ['platform_public_key', 'platform_certificate'],
            'platformTrust',
            '',
            ConfigurationException::class,
        );

        return new self(
            ConfigValue::string($data, ['appid'], 'appid', '', ConfigurationException::class),
            ConfigValue::string($data, ['mch_id', 'mchid'], 'mchId', '', ConfigurationException::class),
            new PemSigningKeyProvider(
                $serial,
                ConfigValue::string($data, ['merchant_private_key', 'cert_private'], 'merchantPrivateKey', '', ConfigurationException::class),
            ),
            new StaticTrustMaterialProvider(['wechat.payment' => [$platformSerial => $platformMaterial]]),
            new Endpoint(ConfigValue::string($data, ['endpoint'], 'endpoint', 'https://api.mch.weixin.qq.com', ConfigurationException::class)),
        );
    }

    /** 校验微信支付商户身份字段。 */
    public function validate(): void
    {
        if (trim($this->appid) === '' || trim($this->mchId) === '') {
            throw new ConfigurationException('微信支付 `appid` 与 `mchId` 不能为空');
        }
    }
}
