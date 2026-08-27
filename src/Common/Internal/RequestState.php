<?php

declare(strict_types=1);

namespace We\Common\Internal;

use Psr\Http\Message\StreamInterface;
use We\Common\MultipartPart;
use We\Common\Resource;

/**
 * 保存通道管线使用的不可变请求状态。
 *
 * @internal
 */
final class RequestState
{
    public const BODY_EMPTY = 'empty';

    public const BODY_JSON = 'json';

    public const BODY_FORM = 'form';

    public const BODY_RAW = 'raw';

    public const BODY_STREAM = 'stream';

    public const BODY_MULTIPART = 'multipart';

    public const IDENTITY_DEFAULT = 'default';

    public const IDENTITY_ANONYMOUS = 'anonymous';

    public const IDENTITY_WECHAT_AUTHORIZER = 'wechat_authorizer';

    public const IDENTITY_ALIPAY_USER = 'alipay_user';

    public const IDENTITY_ALIPAY_APP = 'alipay_app';

    /**
     * @param list<array{0:string,1:string}> $query
     * @param list<array{0:string,1:string}> $headers
     * @param list<MultipartPart> $parts
     */
    public function __construct(
        public readonly string $method,
        public readonly Resource|string $target,
        public readonly array $query = [],
        public readonly array $headers = [],
        public readonly string $bodyType = self::BODY_EMPTY,
        public readonly mixed $body = null,
        public readonly ?string $mediaType = null,
        public readonly ?int $bodyLength = null,
        public readonly array $parts = [],
        public readonly string $identity = self::IDENTITY_DEFAULT,
        public readonly ?string $credentialId = null,
        public readonly bool $rawMedia = false,
        public readonly ?StreamInterface $destination = null,
        public readonly ?string $digestAlgorithm = null,
        public readonly ?string $expectedDigest = null,
        public readonly ?string $sensitiveKeyId = null,
        public readonly int $timeoutMilliseconds = 20_000,
        public readonly int $maxResponseBytes = 67_108_864,
    ) {}

    public function hasHeader(string $name): bool
    {
        foreach ($this->headers as [$candidate]) {
            if (strcasecmp($candidate, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    public function hasQuery(string $name): bool
    {
        foreach ($this->query as [$candidate]) {
            if ($candidate === $name) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string,list<string>> */
    public function headerMap(): array
    {
        $headers = [];
        foreach ($this->headers as [$name, $value]) {
            $headers[$name][] = $value;
        }

        return $headers;
    }

    /**
     * @param null|list<array{0:string,1:string}> $query
     * @param null|list<array{0:string,1:string}> $headers
     * @param null|list<MultipartPart> $parts
     */
    public function with(
        ?array $query = null,
        ?array $headers = null,
        ?string $bodyType = null,
        mixed $body = null,
        ?string $mediaType = null,
        ?int $bodyLength = null,
        ?array $parts = null,
        ?string $identity = null,
        ?string $credentialId = null,
        ?bool $rawMedia = null,
        ?StreamInterface $destination = null,
        ?string $digestAlgorithm = null,
        ?string $expectedDigest = null,
        ?string $sensitiveKeyId = null,
        ?int $timeoutMilliseconds = null,
        ?int $maxResponseBytes = null,
    ): self {
        return new self(
            $this->method,
            $this->target,
            $query ?? $this->query,
            $headers ?? $this->headers,
            $bodyType ?? $this->bodyType,
            $bodyType === null ? $this->body : $body,
            $bodyType === null ? $this->mediaType : $mediaType,
            $bodyType === null ? $this->bodyLength : $bodyLength,
            $bodyType === null ? $this->parts : ($parts ?? []),
            $identity ?? $this->identity,
            $identity === null ? $this->credentialId : $credentialId,
            $rawMedia ?? $this->rawMedia,
            $destination ?? $this->destination,
            $destination === null ? $this->digestAlgorithm : $digestAlgorithm,
            $destination === null ? $this->expectedDigest : $expectedDigest,
            $sensitiveKeyId ?? $this->sensitiveKeyId,
            $timeoutMilliseconds ?? $this->timeoutMilliseconds,
            $maxResponseBytes ?? $this->maxResponseBytes,
        );
    }
}
