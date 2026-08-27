<?php

declare(strict_types=1);

namespace We\Common\Transport;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use We\Common\Exception\TransportException;

/** 发送已完成编码和签名的同步 HTTP 请求。 */
interface HttpTransportInterface
{
    /**
     * 按原样同步发送一次最终 PSR-7 请求；超时单位为毫秒，且不得跟随重定向。
     *
     * @throws TransportException DNS、TLS、连接、超时或底层 HTTP 传输失败
     */
    public function send(RequestInterface $request, int $timeoutMilliseconds = 20_000): ResponseInterface;
}
