<?php

declare(strict_types=1);

namespace We\Exception;

/**
 * 微信回调、微信支付响应或通知签名验证失败时抛出的异常。
 */
final class SignatureException extends WechatException {}
