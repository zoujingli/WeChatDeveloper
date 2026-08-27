<?php

declare(strict_types=1);

namespace We\Common\Transport;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * 按 RFC 3986 构造确定性查询字符串。
 *
 * @internal
 */
final class UriBuilder
{
    /** @param list<array{0:string,1:string}> $query */
    public static function build(string $base, string $path, array $query): UriInterface
    {
        $uri = new Uri(rtrim($base, '/') . '/' . ltrim($path, '/'));
        $pairs = [];
        foreach ($query as [$name, $value]) {
            $pairs[] = rawurlencode($name) . '=' . rawurlencode($value);
        }

        return $uri->withQuery(implode('&', $pairs));
    }
}
