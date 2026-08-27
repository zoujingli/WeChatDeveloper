<?php

declare(strict_types=1);

namespace We\Common\Exception;

/** 请求签名生成失败或平台响应验签失败。 */
final class SignatureException extends ProtocolException {}
