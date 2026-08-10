<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\TestCase;

/**
 * 面向使用者的 Markdown 文档契约测试。
 * @internal
 * @coversNothing
 */
final class DocumentationTest extends TestCase
{
    /** @var list<string> */
    private const DOCUMENTS = [
        'docs/configuration.md',
        'docs/cache.md',
        'docs/wechat.md',
        'docs/payments.md',
        'docs/alipay.md',
        'docs/exceptions.md',
        'docs/testing.md',
        'docs/design.md',
        'docs/migration-2.0.md',
    ];

    public function testReadmeLinksEveryTopicDocument(): void
    {
        $readme = self::read('README.md');

        foreach (self::DOCUMENTS as $document) {
            self::assertFileExists(self::root() . '/' . $document);
            self::assertStringContainsString('(' . $document . ')', $readme);
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
            '不提供兼容层',
        ] as $requiredText) {
            self::assertStringContainsString($requiredText, $migration);
        }
    }

    public function testPhpExamplesAreSyntacticallyValid(): void
    {
        $documents = array_merge(['README.md'], self::DOCUMENTS);
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
