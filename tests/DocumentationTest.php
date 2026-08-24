<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\TestCase;
use We\Client;

/**
 * 面向使用者的 Markdown 文档契约测试。
 * @internal
 * @coversNothing
 */
final class DocumentationTest extends TestCase
{
    /** @var list<string> */
    private const DOCUMENTS = [
        'docs/index.md',
        'docs/configuration.md',
        'docs/api.md',
        'docs/cache.md',
        'docs/wechat.md',
        'docs/payments.md',
        'docs/alipay.md',
        'docs/exceptions.md',
        'docs/testing.md',
        'docs/design.md',
        'docs/migration-2.0.md',
    ];

    /** @var list<string> */
    private const ROOT_DOCUMENTS = [
        'README.md',
        'CHANGELOG.md',
    ];

    /** @var list<string> */
    private const ROOT_FACTORIES = [
        'wechatPlatform',
        'wechatWxapp',
        'wechatService',
        'wechatPayment',
        'alipayPlatform',
        'alipayPayment',
    ];

    public function testReadmeLinksEveryTopicDocument(): void
    {
        $readme = self::read('README.md');

        foreach (self::DOCUMENTS as $document) {
            self::assertFileExists(self::root() . '/' . $document);
            self::assertStringContainsString('(' . $document . ')', $readme);
        }
        self::assertStringContainsString('(CHANGELOG.md)', $readme);
    }

    public function testDocumentationIndexLinksEveryTopicDocument(): void
    {
        $index = self::read('docs/index.md');

        foreach (self::DOCUMENTS as $document) {
            if ($document === 'docs/index.md') {
                continue;
            }
            self::assertStringContainsString('(' . basename($document) . ')', $index);
        }
        self::assertStringContainsString('(../CHANGELOG.md)', $index);
    }

    public function testApiReferenceCoversEveryRootFactory(): void
    {
        $api = self::read('docs/api.md');

        foreach (self::ROOT_FACTORIES as $factory) {
            self::assertStringContainsString($factory . '()', $api);
        }
    }

    public function testEveryRootFactoryHasPhpDoc(): void
    {
        $client = new \ReflectionClass(Client::class);

        foreach (self::ROOT_FACTORIES as $factory) {
            self::assertIsString($client->getMethod($factory)->getDocComment(), $factory . ' 缺少 PHPDoc');
        }
    }

    public function testChangelogSummarizesThe2ReleaseAreas(): void
    {
        $changelog = self::read('CHANGELOG.md');

        foreach ([
            '六个显式类型工厂',
            'SdkException',
            '微信支付普通 JSON 响应',
            'downloadBill()',
            'PsrSimpleCacheStore',
            '配置字段校验后保持只读',
            'Guzzle 安全基线',
            'PHP 8.1、8.2、8.3、8.4',
        ] as $requiredText) {
            self::assertStringContainsString($requiredText, $changelog);
        }
    }

    public function testMigrationGuideCoversRequiredBreakingChanges(): void
    {
        $migration = self::read('docs/migration-2.0.md');

        foreach ([
            'PHP 8.1',
            'We\Client',
            'alipay_public_key',
            'platform_public_key',
            'cert_public',
            'SdkException',
            'StoreCacheInterface',
            '%2E',
            '不提供兼容层',
        ] as $requiredText) {
            self::assertStringContainsString($requiredText, $migration);
        }
    }

    public function testPhpExamplesAreSyntacticallyValid(): void
    {
        $documents = array_merge(self::ROOT_DOCUMENTS, self::DOCUMENTS);
        $blockCount = 0;

        foreach ($documents as $document) {
            $markdown = self::read($document);
            preg_match_all('/```php\s*\R(.*?)```/s', $markdown, $matches);
            foreach ($matches[1] as $snippet) {
                $source = (string)preg_replace('/^\s*<\?php\s*/', '', $snippet);
                $tokens = token_get_all("<?php\n" . $source, TOKEN_PARSE);
                self::assertNotEmpty($tokens, 'Unable to tokenize PHP example in ' . $document);
                ++$blockCount;
            }
        }

        self::assertGreaterThan(0, $blockCount);
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents(self::root() . '/' . $path);
        self::assertIsString($contents, 'Unable to read ' . $path);

        return $contents;
    }

    private static function root(): string
    {
        return dirname(__DIR__);
    }
}
