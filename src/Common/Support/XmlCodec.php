<?php

declare(strict_types=1);

namespace We\Common\Support;

use We\Common\Exception\ProtocolException;

/**
 * 将 XML 解码为保留重复节点的数组。
 *
 * @internal
 */
final class XmlCodec
{
    /** @return array<string,mixed> */
    public static function decode(string $xml): array
    {
        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$element instanceof \SimpleXMLElement) {
            throw new ProtocolException('平台 XML 响应格式无效');
        }

        return self::normalize($element);
    }

    /** @return array<string,mixed> */
    private static function normalize(\SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->children() as $name => $child) {
            $value = $child->children()->count() > 0 ? self::normalize($child) : (string)$child;
            if (array_key_exists($name, $result)) {
                if (!is_array($result[$name]) || !array_is_list($result[$name])) {
                    $result[$name] = [$result[$name]];
                }
                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}
