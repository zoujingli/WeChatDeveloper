<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use We\Support\Xml;

/**
 * 微信 XML 编解码工具测试用例。
 * @internal
 */
#[CoversClass(Xml::class)]
final class XmlTest extends TestCase
{
    /**
     * 测试重复节点和嵌套节点可正确解码。
     */
    public function testDecodeKeepsRepeatedAndNestedNodes(): void
    {
        $xml = '<xml><Articles><item><Title><![CDATA[A]]></Title></item><item><Title><![CDATA[B]]></Title></item></Articles><Tag><![CDATA[x]]></Tag><Tag><![CDATA[y]]></Tag></xml>';

        $data = Xml::decode($xml);

        $this->assertSame('A', $data['Articles']['item'][0]['Title']);
        $this->assertSame('B', $data['Articles']['item'][1]['Title']);
        $this->assertSame(['x', 'y'], $data['Tag']);
    }

    /**
     * 测试嵌套数组、列表节点和包含 CDATA 结束标记的文本可正确编码。
     */
    public function testEncodeSupportsNestedListsAndCdataEndMarker(): void
    {
        $xml = Xml::encode([
            'Articles' => [
                'item' => [
                    ['Title' => 'A', 'Description' => 'hello ]]> world'],
                    ['Title' => 'B', 'Description' => 'second'],
                ],
            ],
        ]);

        $data = Xml::decode($xml);

        $this->assertSame('A', $data['Articles']['item'][0]['Title']);
        $this->assertSame('hello ]]> world', $data['Articles']['item'][0]['Description']);
        $this->assertSame('B', $data['Articles']['item'][1]['Title']);
    }
}
