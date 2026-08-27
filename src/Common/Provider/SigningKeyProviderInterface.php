<?php

declare(strict_types=1);

namespace We\Common\Provider;

use We\Common\Exception\SignatureException;

/** 提供支持不可导出密钥实现的 RSA 签名接口。 */
interface SigningKeyProviderInterface
{
    /** 返回写入平台协议的签名密钥 ID 或证书序列号。 */
    public function keyId(): string;

    /**
     * 对原始报文字节签名并返回 Base64 编码结果。
     *
     * @throws SignatureException 签名能力不可用或签名失败
     */
    public function sign(string $message, int|string $algorithm = OPENSSL_ALGO_SHA256): string;
}
