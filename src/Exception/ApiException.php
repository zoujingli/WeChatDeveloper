<?php

declare(strict_types=1);

namespace We\Exception;

/**
 * 微信平台或微信支付 API 返回业务错误、HTTP 错误或无效响应时抛出的异常。
 */
final class ApiException extends WechatException {}
