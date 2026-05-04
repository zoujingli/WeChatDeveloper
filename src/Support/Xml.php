<?php

declare(strict_types=1);

namespace We\Support;

use SimpleXMLElement;
use We\Exception\WechatException;

final class Xml
{
    /**
     * @return array<string,mixed>
     */
    public static function decode(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);
        if (!$element instanceof SimpleXMLElement) {
            throw new WechatException('微信 XML 格式无效');
        }

        return self::normalize($element);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function encode(array $data): string
    {
        $content = '<xml>';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
            }
            $value = (string)$value;
            $content .= sprintf('<%1$s><![CDATA[%2$s]]></%1$s>', $key, $value);
        }

        return $content . '</xml>';
    }

    /**
     * @return array<string,mixed>
     */
    private static function normalize(SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->children() as $key => $value) {
            $children = $value->children();
            $result[$key] = $children->count() > 0 ? self::normalize($value) : (string)$value;
        }

        return $result;
    }
}
