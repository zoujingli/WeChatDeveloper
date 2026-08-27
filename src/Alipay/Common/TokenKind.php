<?php

declare(strict_types=1);

namespace We\Alipay\Common;

/** 支付宝调用身份 Token 的类型。 */
enum TokenKind: string
{
    case AlipayUser = 'alipay.user';
    case AlipayApp = 'alipay.app';
}
