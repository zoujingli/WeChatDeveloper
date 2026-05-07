<?php

declare(strict_types=1);

/**
 * 平台接口请求异常。
 */

namespace We\Exception;

/**
 * HTTP 请求失败或平台接口返回错误时抛出的异常。
 */
final class ApiException extends WechatException {}
