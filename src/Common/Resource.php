<?php

declare(strict_types=1);

namespace We\Common;

use We\Common\Exception\InvalidCallException;

/** 由可信平台响应创建并绑定来源平台通道的 HTTPS 资源。 */
final class Resource
{
    private function __construct(
        public readonly string $channel,
        public readonly string $url,
        public readonly bool $signed,
        public readonly ?string $digestAlgorithm,
        public readonly ?string $expectedDigest,
    ) {}

    /** @internal 仅由 `Response` 根据已完成协议校验的 JSON 响应创建。 */
    public static function issue(
        string $channel,
        string $url,
        bool $signed,
        ?string $digestAlgorithm = null,
        ?string $expectedDigest = null,
    ): self {
        $parts = parse_url($url);
        if (
            filter_var($url, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || strtolower((string)($parts['scheme'] ?? '')) !== 'https'
            || !is_string($parts['host'] ?? null)
            || $parts['host'] === ''
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            throw new InvalidCallException('派生资源必须使用不含用户信息的 HTTPS URL');
        }
        if (($digestAlgorithm === null) !== ($expectedDigest === null)) {
            throw new InvalidCallException('摘要算法与摘要值必须同时提供');
        }
        if ($digestAlgorithm !== null && !in_array(strtolower($digestAlgorithm), hash_algos(), true)) {
            throw new InvalidCallException('派生资源摘要算法不受支持');
        }

        return new self($channel, $url, $signed, $digestAlgorithm, $expectedDigest);
    }
}
