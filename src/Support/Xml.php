<?php

declare(strict_types=1);

namespace We\Support;

use We\Exception\WechatException;

/**
 * 微信 XML 编解码工具。
 *
 * 用于处理微信消息 XML：解码时读取 CDATA 文本，编码时将字段值写入 CDATA。
 */
final class Xml
{
    /**
     * 将微信消息 XML 解析为数组。
     *
     * @return array<string,mixed>
     */
    public static function decode(string $xml): array
    {
        $xml = trim($xml);
        if ($xml === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_use_internal_errors($previous);
        if (!$element instanceof \SimpleXMLElement) {
            throw new WechatException('微信 XML 格式无效');
        }

        return self::normalize($element);
    }

    /**
     * 将数组编码为微信消息 XML。
     *
     * @param array<string,mixed> $data
     */
    public static function encode(array $data): string
    {
        $content = '<xml>';
        foreach ($data as $key => $value) {
            $content .= self::encodeNode((string)$key, $value);
        }

        return $content . '</xml>';
    }

    /**
     * 递归编码单个 XML 节点；列表数组会重复输出同名节点。
     */
    private static function encodeNode(string $key, mixed $value): string
    {
        self::assertNodeName($key);
        if ($value === []) {
            return sprintf('<%1$s></%1$s>', $key);
        }
        if (is_array($value) && array_is_list($value)) {
            $content = '';
            foreach ($value as $item) {
                $content .= self::encodeNode($key, $item);
            }

            return $content;
        }
        if (is_array($value)) {
            $content = '';
            foreach ($value as $childKey => $childValue) {
                $content .= self::encodeNode((string)$childKey, $childValue);
            }

            return sprintf('<%1$s>%2$s</%1$s>', $key, $content);
        }

        return sprintf('<%1$s><![CDATA[%2$s]]></%1$s>', $key, self::cdata((string)$value));
    }

    /**
     * 递归转换 SimpleXML 节点为普通数组。
     *
     * @return array<string,mixed>
     */
    private static function normalize(\SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->children() as $key => $value) {
            $children = $value->children();
            $node = $children->count() > 0 ? self::normalize($value) : (string)$value;
            if (array_key_exists($key, $result)) {
                if (!is_array($result[$key]) || !array_is_list($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $node;
                continue;
            }
            $result[$key] = $node;
        }

        return $result;
    }

    /**
     * 转义 CDATA 结束标记，避免字段值破坏 XML 结构。
     */
    private static function cdata(string $value): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }

    /**
     * 校验 XML 节点名，避免生成非法 XML。
     */
    private static function assertNodeName(string $key): void
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_.:-]*$/', $key) !== 1) {
            throw new WechatException('微信 XML 节点名无效: ' . $key);
        }
    }
}
