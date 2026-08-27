<?php

declare(strict_types=1);

namespace We\Common\Support;

use We\Common\Exception\SdkException;

/**
 * 配置数组字符串字段读取器：统一处理字段别名，并拒绝隐式类型转换。
 * @internal
 */
final class ConfigValue
{
    /**
     * @param array<string,mixed> $data
     * @param list<string> $keys
     * @param class-string<SdkException> $exceptionClass
     */
    public static function string(
        array $data,
        array $keys,
        string $field,
        string $default,
        string $exceptionClass,
    ): string {
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                continue;
            }
            if (!is_string($data[$key])) {
                throw new $exceptionClass('`' . $field . '` 必须是字符串');
            }

            return $data[$key];
        }

        return $default;
    }
}
