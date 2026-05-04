<?php

declare(strict_types=1);

namespace We\Platform\Alipay;

use GuzzleHttp\ClientInterface;
use We\Config\AlipayPaymentConfig;

final class PaymentClient extends PlatformClient
{
    public function __construct(AlipayPaymentConfig $config, ?ClientInterface $http = null)
    {
        parent::__construct($config, $http);
    }

    /**
     * @param array<string,mixed> $bizContent
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public function refund(array $bizContent, array $extra = []): array
    {
        return $this->request('alipay.trade.refund', $bizContent, $extra);
    }

    /**
     * @param array<string,mixed> $bizContent
     */
    public function page(array $bizContent, array $extra = []): string
    {
        $params = [
            'app_id' => $this->config->appid,
            'method' => 'alipay.trade.page.pay',
            'format' => $this->config->format,
            'charset' => $this->config->charset,
            'sign_type' => $this->config->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->config->version,
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        ];
        foreach ($extra as $key => $value) {
            $params[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $params['sign'] = $this->sign($params);

        return $this->config->gateway . '?' . http_build_query($params);
    }

    /**
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
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function post(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'POST', $options);
    }

    /**
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    public function get(string $uriOrPath, array $params = [], array $options = []): array
    {
        return $this->call($uriOrPath, $params, 'GET', $options);
    }
}
