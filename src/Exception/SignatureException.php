<?php

declare(strict_types=1);
/**
 * This file is part of HyperfAdmin.
 *
 * @Link https://thinkadmin.top
 * @Author Anyon<zoujingli@qq.com>
 */

namespace We\Exception;

/**
 * 微信回调、微信支付通知或支付宝通知签名验证失败时抛出的异常。
 */
final class SignatureException extends WechatException {}
