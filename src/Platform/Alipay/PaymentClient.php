<?php

declare(strict_types=1);

/**
 * 支付宝支付客户端。
 */

namespace We\Platform\Alipay;

use GuzzleHttp\ClientInterface;
use We\Config\AlipayPaymentConfig;

/**
 * 支付宝支付客户端。
 *
 * 在支付宝开放平台网关能力之上提供电脑网站支付与交易退款快捷方法。
 */
final class PaymentClient extends PlatformClient
{
    /**
     * 创建支付宝支付客户端。
     */
    public function __construct(AlipayPaymentConfig $config, ?ClientInterface $http = null)
    {
        parent::__construct($config, $http);
    }

    /**
     * 调用支付宝交易退款接口 `alipay.trade.refund`。
     *
     * @param array<string,mixed> $bizContent
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public function refund(array $bizContent, array $extra = []): array
    {
        return $this->request('alipay.trade.refund', $bizContent, $extra);
    }

    /**
     * 生成电脑网站支付接口 `alipay.trade.page.pay` 跳转地址。
     *
     * @param array<string,mixed> $bizContent
     */
    public function page(array $bizContent, array $extra = []): string
    {
        $params = $this->buildGatewayParams('alipay.trade.page.pay', $bizContent, $extra);

        return $this->config->gateway . '?' . http_build_query($params);
    }

    /**
     * 通用支付调用入口；特殊操作名用于电脑网站支付和退款快捷调用。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function call(string $uriOrPath, array $params = [], string $httpMethod = 'POST', array $options = []): array
    {
        $apiMethod = trim($uriOrPath);
        if ($apiMethod === 'page') {
            return ['url' => $this->page($params, $options)];
        }
        if ($apiMethod === 'refund') {
            return $this->refund($params, $options);
        }

        return $this->request($apiMethod, $params, $options);
    }

    /**
     * 按 POST 语义调用支付宝支付接口。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * 按 GET 语义调用支付宝支付接口。
     *
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }
}
