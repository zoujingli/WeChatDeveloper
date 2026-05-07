<?php

declare(strict_types=1);

/**
 * 平台配置契约。
 */

namespace We\Contract;

/**
 * 平台配置统一契约：所有配置对象必须支持数组构造，并在构造阶段完成平台必填项校验。
 */
interface ConfigInterface
{
    /**
     * 通过数组创建配置对象。
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static;

    /**
     * 校验平台配置的必填字段、密钥格式等约束；校验失败时抛出 SDK 异常。
     */
    public function validate(): void;
}
