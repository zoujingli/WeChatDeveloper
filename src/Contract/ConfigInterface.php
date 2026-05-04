<?php

declare(strict_types=1);

namespace We\Contract;

/**
 * 平台配置统一契约：所有通道配置必须能从数组构造，并在构造阶段完成自身业务校验。
 */
interface ConfigInterface
{
    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): static;

    /**
     * 校验配置字段完整性、密钥格式等通道级约束；失败时抛出 SDK 异常。
     */
    public function validate(): void;
}
