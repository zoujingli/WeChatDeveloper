<?php

declare(strict_types=1);

namespace We\Config;

use We\Contract\ConfigInterface;
use We\Exception\WechatException;
use We\Support\ConfigValue;
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
        public readonly string $appid,
        public readonly string $mchId,
        public readonly string $apiV3Key,
        public readonly string $merchantSerial,
        public readonly string $merchantPrivateKey,
        /** 微信支付平台证书 PEM；未配置 platformPublicKey 时用于回调验签。 */
        public readonly string $platformCertificate = '',
        /** 微信支付平台公钥 PEM，优先用于回调验签。 */
        public readonly string $platformPublicKey = '',
        /** 微信支付平台证书/公钥序列号；非空时会校验回调头 Wechatpay-Serial。 */
        public readonly string $platformSerial = '',
        /** 通知时间戳允许偏差秒数；0 表示显式关闭新鲜度校验。 */
        public readonly int $notificationToleranceSeconds = 300,
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
        CredentialValidator::assertPrivateKey($this->merchantPrivateKey, 'merchantPrivateKey', exceptionClass: WechatException::class);
        if ($this->platformCertificate === '' && $this->platformPublicKey === '') {
            throw new WechatException('platformCertificate 或 platformPublicKey 不能为空');
        }
        if (trim($this->platformSerial) === '') {
            throw new WechatException('platformSerial 不能为空');
        }
        if ($this->notificationToleranceSeconds < 0) {
            throw new WechatException('notificationToleranceSeconds 不能小于 0');
        }
        if ($this->platformCertificate !== '') {
            CredentialValidator::assertPublicKey($this->platformCertificate, 'platformCertificate', exceptionClass: WechatException::class);
        }
        if ($this->platformPublicKey !== '') {
            CredentialValidator::assertPublicKey($this->platformPublicKey, 'platformPublicKey', exceptionClass: WechatException::class);
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
            ConfigValue::string($data, ['appid'], 'appid', '', WechatException::class),
            ConfigValue::string($data, ['mch_id', 'mchid'], 'mchId', '', WechatException::class),
            ConfigValue::string($data, ['api_v3_key', 'mch_v3_key'], 'apiV3Key', '', WechatException::class),
            ConfigValue::string($data, ['merchant_serial', 'cert_serial'], 'merchantSerial', '', WechatException::class),
            ConfigValue::string($data, ['merchant_private_key', 'cert_private'], 'merchantPrivateKey', '', WechatException::class),
            ConfigValue::string($data, ['platform_certificate'], 'platformCertificate', '', WechatException::class),
            ConfigValue::string($data, ['platform_public_key'], 'platformPublicKey', '', WechatException::class),
            ConfigValue::string($data, ['platform_serial'], 'platformSerial', '', WechatException::class),
            self::notificationTolerance($data),
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function notificationTolerance(array $data): int
    {
        if (!array_key_exists('notification_tolerance_seconds', $data)) {
            return 300;
        }
        $value = $data['notification_tolerance_seconds'];
        if (is_int($value) && $value >= 0) {
            return $value;
        }
        if (is_string($value) && preg_match('/^(0|[1-9]\d*)$/D', $value) === 1) {
            $parsed = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if (is_int($parsed)) {
                return $parsed;
            }
        }

        throw new WechatException('notification_tolerance_seconds 必须是 0 或正整数');
    }
}
