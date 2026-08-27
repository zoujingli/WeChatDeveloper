<?php

declare(strict_types=1);

namespace We\Tests;

use PHPUnit\Framework\TestCase;
use We\Alipay\AliPayConfig;
use We\Alipay\AliRestConfig;
use We\Alipay\Common\TokenProviderInterface;
use We\AliPayClient;
use We\AliRestClient;
use We\Common\Contract\ChannelInterface;
use We\Common\Protocol\ExternalResourcePolicyInterface;
use We\Common\Provider\SigningKeyProviderInterface;
use We\Common\Provider\TrustMaterialProviderInterface;
use We\Common\Request;
use We\Common\Response;
use We\Common\Transport\HttpTransportInterface;
use We\Wechat\Common\StoreCacheInterface;
use We\Wechat\WeChatConfig;
use We\Wechat\WxAppConfig;
use We\Wechat\WxOpen\ComponentTicketProviderInterface;
use We\Wechat\WxOpen\StoreTokenInterface;
use We\Wechat\WxOpenConfig;
use We\Wechat\WxPayConfig;
use We\WeChatClient;
use We\WxAppClient;
use We\WxOpenClient;
use We\WxPayClient;

/**
 * 面向使用者的 Markdown 文档契约测试。
 *
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
        'docs/style-guide.md',
    ];

    /** @var list<string> */
    private const ROOT_DOCUMENTS = [
        'README.md',
        'CHANGELOG.md',
        'CONTRIBUTING.md',
        'SECURITY.md',
    ];

    /** @var list<string> */
    private const COMMON_INTERNAL_TYPES = [
        'src/Common/AbstractClient.php',
        'src/Common/Internal/RequestState.php',
        'src/Common/Internal/RsaVerifier.php',
        'src/Common/Support/ConfigValue.php',
        'src/Common/Support/CredentialValidator.php',
        'src/Common/Support/XmlCodec.php',
        'src/Common/Transport/BodyEncoder.php',
        'src/Common/Transport/EncodedBody.php',
        'src/Common/Transport/Spool.php',
        'src/Common/Transport/Spooler.php',
        'src/Common/Transport/UriBuilder.php',
    ];

    public function testReadmeLinksEveryTopicDocument(): void
    {
        $readme = self::read('README.md');

        foreach (self::DOCUMENTS as $document) {
            self::assertFileExists(self::root() . '/' . $document);
            self::assertStringContainsString('(' . $document . ')', $readme);
        }
        foreach (array_slice(self::ROOT_DOCUMENTS, 1) as $document) {
            self::assertStringContainsString('(' . $document . ')', $readme);
        }
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
        foreach (array_slice(self::ROOT_DOCUMENTS, 1) as $document) {
            self::assertStringContainsString('(../' . $document . ')', $index);
        }
    }

    public function testApiReferenceCoversTheCoreFlow(): void
    {
        $api = self::read('docs/api.md');

        foreach (['WeChatClient', 'AliPayClient', 'call(Request)', 'Runtime', 'Request', 'Response', 'rawMedia()'] as $entry) {
            self::assertStringContainsString($entry, $api);
        }
    }

    public function testApiReferenceCoversEveryPublicRequestAndResponseMethod(): void
    {
        $api = self::read('docs/api.md');

        foreach ([Request::class, Response::class] as $className) {
            $class = new \ReflectionClass($className);
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $className || $method->isConstructor()) {
                    continue;
                }
                $doc = $method->getDocComment();
                if (is_string($doc) && str_contains($doc, '@internal')) {
                    continue;
                }
                self::assertMatchesRegularExpression(
                    '/\b' . preg_quote($method->getName(), '/') . '\s*\(/',
                    $api,
                    $className . '::' . $method->getName() . ' 未写入公开 API 文档',
                );
            }
        }
    }

    public function testEveryPublicInterfaceMethodDefinesItsContract(): void
    {
        foreach ([
            ChannelInterface::class,
            ExternalResourcePolicyInterface::class,
            SigningKeyProviderInterface::class,
            TrustMaterialProviderInterface::class,
            HttpTransportInterface::class,
            StoreCacheInterface::class,
            ComponentTicketProviderInterface::class,
            StoreTokenInterface::class,
            TokenProviderInterface::class,
        ] as $interfaceName) {
            $interface = new \ReflectionClass($interfaceName);
            foreach ($interface->getMethods() as $method) {
                self::assertIsString(
                    $method->getDocComment(),
                    $interfaceName . '::' . $method->getName() . ' 缺少接口契约 PHPDoc',
                );
            }
        }
    }

    public function testEveryPublicTypeIsNamedInUserReferences(): void
    {
        $reference = '';
        foreach ([
            'README.md',
            'docs/api.md',
            'docs/configuration.md',
            'docs/cache.md',
            'docs/wechat.md',
            'docs/payments.md',
            'docs/alipay.md',
            'docs/exceptions.md',
        ] as $document) {
            $reference .= self::read($document);
        }

        foreach (self::sourceFiles() as $file) {
            $source = self::read($file);
            if (str_contains(self::typeDocComment($source), '@internal')) {
                continue;
            }
            preg_match(
                '/^(?:(?:abstract|final|readonly)\s+)*(?:class|interface|enum)\s+(\w+)/m',
                $source,
                $matches,
            );
            self::assertArrayHasKey(1, $matches, $file . ' 无法解析公开类型名称');
            self::assertStringContainsString($matches[1], $reference, $file . ' 未写入用户参考文档');
        }
    }

    public function testPrimaryReferencesUseCompleteConfigNamespaces(): void
    {
        foreach (['README.md', 'docs/api.md', 'docs/configuration.md'] as $document) {
            $contents = self::read($document);
            foreach ([
                WeChatConfig::class,
                WxAppConfig::class,
                WxOpenConfig::class,
                WxPayConfig::class,
                AliPayConfig::class,
                AliRestConfig::class,
            ] as $configClass) {
                self::assertStringContainsString($configClass, $contents, $document . ' 缺少完整配置命名空间 ' . $configClass);
            }
        }
    }

    public function testConfigurationReferenceCoversEveryConfigConstructorParameter(): void
    {
        $configuration = self::read('docs/configuration.md');
        foreach ([
            WeChatConfig::class,
            WxAppConfig::class,
            WxOpenConfig::class,
            WxPayConfig::class,
            AliPayConfig::class,
            AliRestConfig::class,
        ] as $configClass) {
            $constructor = (new \ReflectionClass($configClass))->getConstructor();
            self::assertNotNull($constructor);
            $parameters = array_map(
                static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
                $constructor->getParameters(),
            );
            self::assertStringContainsString(
                '`' . implode('`、`', $parameters) . '`',
                $configuration,
                $configClass . ' 的构造参数未完整写入配置文档',
            );
        }
    }

    public function testChannelClientFactoriesHavePhpDoc(): void
    {
        foreach ([
            WeChatClient::class => WeChatConfig::class,
            WxAppClient::class => WxAppConfig::class,
            WxOpenClient::class => WxOpenConfig::class,
            WxPayClient::class => WxPayConfig::class,
            AliPayClient::class => AliPayConfig::class,
            AliRestClient::class => AliRestConfig::class,
        ] as $clientClass => $configClass) {
            $client = new \ReflectionClass($clientClass);
            self::assertIsString($client->getMethod('mk')->getDocComment(), $clientClass . '::mk 缺少 PHPDoc');
            self::assertIsString($client->getMethod('call')->getDocComment(), $clientClass . '::call 缺少 PHPDoc');
            self::assertSame(
                $configClass,
                (string)$client->getMethod('mk')->getParameters()[0]->getType(),
                $clientClass . ' 必须接受同生态根目录中的具名 Config',
            );
        }
    }

    public function testChannelConfigsLiveDirectlyUnderEcosystemRoots(): void
    {
        foreach ([
            'Wechat' => [WeChatConfig::class, WxAppConfig::class, WxOpenConfig::class, WxPayConfig::class],
            'Alipay' => [AliPayConfig::class, AliRestConfig::class],
        ] as $ecosystem => $configClasses) {
            $expectedFiles = array_map(
                static fn (string $configClass): string => (new \ReflectionClass($configClass))->getShortName() . '.php',
                $configClasses,
            );
            $actualFiles = array_map('basename', glob(self::root() . '/src/' . $ecosystem . '/*.php') ?: []);
            sort($expectedFiles);
            sort($actualFiles);
            self::assertSame($expectedFiles, $actualFiles, $ecosystem . ' 根目录只能包含对应通道 Config');

            foreach ($configClasses as $configClass) {
                $config = new \ReflectionClass($configClass);
                self::assertSame(
                    realpath(self::root() . '/src/' . $ecosystem . '/' . $config->getShortName() . '.php'),
                    realpath((string)$config->getFileName()),
                    $configClass . ' 必须直接放在生态根目录',
                );
            }
        }
    }

    public function testChannelClientsLiveDirectlyUnderSourceRoot(): void
    {
        $rootFiles = array_map('basename', glob(self::root() . '/src/*.php') ?: []);
        sort($rootFiles);
        self::assertSame([
            'AliPayClient.php',
            'AliRestClient.php',
            'WeChatClient.php',
            'WxAppClient.php',
            'WxOpenClient.php',
            'WxPayClient.php',
        ], $rootFiles, 'src 根目录只能包含六个通道 Client');

        foreach ([
            WeChatClient::class,
            WxAppClient::class,
            WxOpenClient::class,
            WxPayClient::class,
            AliPayClient::class,
            AliRestClient::class,
        ] as $clientClass) {
            $client = new \ReflectionClass($clientClass);
            self::assertSame(
                realpath(self::root() . '/src/' . $client->getShortName() . '.php'),
                realpath((string)$client->getFileName()),
                $clientClass . ' 必须直接放在 src 目录',
            );
        }
    }

    public function testSourceDirectoriesFollowEcosystemLayout(): void
    {
        $directories = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::root() . '/src', \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                $directories[] = substr($entry->getPathname(), strlen(self::root() . '/src/'));
            }
        }
        sort($directories);

        self::assertSame([
            'Alipay',
            'Alipay/Common',
            'Alipay/Common/Internal',
            'Common',
            'Common/Config',
            'Common/Contract',
            'Common/Exception',
            'Common/Internal',
            'Common/Protocol',
            'Common/Provider',
            'Common/Support',
            'Common/Transport',
            'Wechat',
            'Wechat/Common',
            'Wechat/Common/Internal',
            'Wechat/WxOpen',
            'Wechat/WxOpen/Internal',
        ], $directories, 'src 子目录必须按跨生态、生态公共和必要的通道专属实现归属');
    }

    public function testChannelModulesDoNotDependOnSiblingChannelsOrOtherEcosystems(): void
    {
        $channelPrefixes = [
            'Wechat' => ['WeChat', 'WxApp', 'WxOpen', 'WxPay'],
            'Alipay' => ['AliPay', 'AliRest'],
        ];

        foreach (['Wechat' => ['WxOpen'], 'Alipay' => []] as $ecosystem => $channels) {
            foreach ($channels as $channel) {
                $root = self::root() . '/src/' . $ecosystem . '/' . $channel;
                foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
                    if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                        continue;
                    }
                    $source = (string)file_get_contents($file->getPathname());
                    $otherEcosystem = $ecosystem === 'Wechat' ? 'Alipay' : 'Wechat';
                    self::assertStringNotContainsString(
                        'We\\' . $otherEcosystem . '\\',
                        $source,
                        $ecosystem . '/' . $channel . ' 不得依赖其他生态 ' . $otherEcosystem,
                    );
                    foreach (array_diff($channelPrefixes[$ecosystem], [$channel]) as $sibling) {
                        self::assertStringNotContainsString(
                            'We\\' . $ecosystem . '\\' . $sibling . '\\',
                            $source,
                            $ecosystem . '/' . $channel . ' 不得依赖兄弟通道 ' . $sibling,
                        );
                    }
                }
            }
        }
    }

    public function testChangelogSummarizesThe2ReleaseAreas(): void
    {
        $changelog = self::read('CHANGELOG.md');

        foreach ([
            'WeChatClient::mk()',
            '微信支付 APIv3',
            '支付宝支付 v2 Gateway',
            '支付宝 REST v3',
            '派生资源',
            '失败关闭',
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
            'WeChatClient::mk',
            'call(Request)',
            'AliPayConfig',
            'AliRestConfig',
            'platform_public_key',
            'cert_public',
            'SignatureException',
            'StoreCacheInterface',
            '%2E',
            '不提供兼容层',
        ] as $requiredText) {
            self::assertStringContainsString($requiredText, $migration);
        }
    }

    public function testPhpExamplesAreSyntacticallyValid(): void
    {
        $blockCount = 0;

        foreach (self::markdownDocuments() as $document) {
            $markdown = self::read($document);
            preg_match_all('/```php\s*\R(.*?)```/s', $markdown, $matches);
            foreach ($matches[1] as $snippet) {
                $source = (string)preg_replace('/^\s*<\?php\s*/', '', $snippet);
                $tokens = token_get_all("<?php\n" . $source, TOKEN_PARSE);
                self::assertNotEmpty($tokens, '无法解析 PHP 示例：' . $document);
                ++$blockCount;
            }
        }

        self::assertGreaterThan(0, $blockCount);
    }

    public function testGeneratedDomainAndResearchDocumentsAreNavigable(): void
    {
        foreach ([
            'CONTEXT.md',
            'docs/adr/',
            'docs/research/platform-call-scenarios.md',
        ] as $target) {
            self::assertStringContainsString('(' . $target . ')', self::read('README.md'));
        }
        self::assertStringContainsString('(../CONTEXT.md)', self::read('docs/index.md'));
        self::assertFileExists(self::root() . '/CONTEXT.md');
        self::assertFileExists(self::root() . '/docs/research/platform-call-scenarios.md');
        for ($number = 1; $number <= 6; ++$number) {
            $matches = glob(self::root() . '/docs/adr/' . sprintf('%04d', $number) . '-*.md');
            self::assertIsArray($matches);
            self::assertCount(1, $matches, 'ADR 编号缺失或重复: ' . $number);
            self::assertStringContainsString('status: accepted', self::read(
                substr($matches[0], strlen(self::root()) + 1),
            ));
        }
    }

    public function testArchitectureDocumentsDescribeCurrentConfigPlacement(): void
    {
        $design = self::read('docs/design.md');
        foreach ([
            'WeChatConfig.php',
            'WxAppConfig.php',
            'WxOpenConfig.php',
            'WxPayConfig.php',
            'AliPayConfig.php',
            'AliRestConfig.php',
        ] as $configFile) {
            self::assertStringContainsString($configFile, $design);
        }

        self::assertStringContainsString('具名 Config 直接位于', self::read('docs/adr/0002-single-call-interface.md'));
        self::assertStringContainsString('六个具名 Config 直接放在', self::read('docs/agents/development.md'));
        self::assertStringContainsString('Config 直接位于生态根目录', self::read('CHANGELOG.md'));
    }

    public function testMarkdownDocumentsFollowBaseStructure(): void
    {
        foreach (self::markdownDocuments() as $document) {
            $markdown = self::read($document);
            self::assertSame(
                1,
                preg_match_all('/^# [^#].*$/m', $markdown),
                $document . ' 必须且只能包含一个一级标题',
            );
            self::assertDoesNotMatchRegularExpression(
                '/[ \t]+$/m',
                $markdown,
                $document . ' 包含行尾空白',
            );
        }
    }

    public function testEveryLocalMarkdownLinkResolves(): void
    {
        foreach (self::markdownDocuments() as $document) {
            preg_match_all('/\[[^\]]+\]\(([^)]+)\)/', self::read($document), $matches);
            foreach ($matches[1] as $target) {
                if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $target) === 1 || str_starts_with($target, '#')) {
                    continue;
                }
                $path = explode('#', $target, 2)[0];
                $resolved = dirname(self::root() . '/' . $document) . '/' . $path;
                self::assertFileExists($resolved, $document . ' 包含无效本地链接: ' . $target);
            }
        }
    }

    public function testSourceFilesFollowTheDocumentationStandard(): void
    {
        foreach (self::sourceFiles() as $file) {
            $source = self::read($file);
            self::assertMatchesRegularExpression(
                '/\A<\?php\s+declare\(strict_types=1\);/',
                $source,
                $file . ' 必须声明 strict_types=1',
            );

            preg_match_all(
                '/^(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+\w+/m',
                $source,
                $types,
            );
            self::assertCount(1, $types[0], $file . ' 必须且只能声明一个顶级类型');
            self::assertNotSame('', self::typeDocComment($source), $file . ' 的顶级类型缺少 PHPDoc');
        }
    }

    public function testInternalTypesAreExplicitlyMarked(): void
    {
        $internalTypes = self::COMMON_INTERNAL_TYPES;
        foreach (self::ecosystemInternalFiles() as $path) {
            $internalTypes[] = substr($path, strlen(self::root()) + 1);
        }

        foreach ($internalTypes as $file) {
            self::assertStringContainsString('@internal', self::typeDocComment(self::read($file)), $file . ' 缺少 @internal');
        }
    }

    public function testEcosystemInternalNamespacesMatchTheirDirectories(): void
    {
        foreach (self::ecosystemInternalFiles() as $path) {
            $file = substr($path, strlen(self::root()) + 1);
            $directory = dirname(substr($file, strlen('src/')));
            self::assertStringContainsString(
                sprintf('namespace We%s%s;', chr(92), str_replace('/', chr(92), $directory)),
                self::read($file),
                $file . ' 的命名空间与生态目录不一致',
            );
        }
    }

    public function testCurrentDocumentationDoesNotReferenceRemovedApiFamilies(): void
    {
        $documents = array_values(array_filter(
            self::markdownDocuments(),
            static fn (string $document): bool => !in_array($document, [
                'CHANGELOG.md',
                'docs/migration-2.0.md',
            ], true),
        ));
        $removedTypes = [
            'WechatPlatformConfig',
            'WechatWxappConfig',
            'WechatServiceConfig',
            'WechatPaymentConfig',
            'AlipayGatewayConfig',
            'AlipayRestConfig',
            'WechatPlatformChannel',
            'WechatWxappChannel',
            'WechatServiceChannel',
            'WechatPaymentChannel',
            'AlipayGatewayChannel',
            'AlipayRestChannel',
            'JsonResult',
            'XmlResult',
            'RawResult',
            'EmptyResult',
            'StreamResult',
        ];
        $removedCommonTypes = [
            'We\MultipartPart',
            'We\Request',
            'We\Resource',
            'We\Response',
            'We\Runtime',
        ];
        $removedNamespaceRoots = [
            'We\Config\\',
            'We\Contract\\',
            'We\Exception\\',
            'We\Protocol\\',
            'We\Provider\\',
            'We\Support\\',
            'We\Transport\\',
            'We\Wechat\OfficialAccount\\',
            'We\Wechat\MiniProgram\\',
            'We\Wechat\OpenPlatform\\',
            'We\Wechat\Pay\\',
            'We\Alipay\Pay\\',
            'We\Alipay\Rest\\',
        ];
        $removedConfigNamespaces = [
            'We\Wechat\WeChat\WeChatConfig',
            'We\Wechat\WxApp\WxAppConfig',
            'We\Wechat\WxOpen\WxOpenConfig',
            'We\Wechat\WxPay\WxPayConfig',
            'We\Alipay\AliPay\AliPayConfig',
            'We\Alipay\AliRest\AliRestConfig',
        ];

        foreach ($documents as $document) {
            $contents = self::read($document);
            self::assertDoesNotMatchRegularExpression(
                '/\b(?:PlatformClient|WxappClient|ServiceClient|PaymentClient|GatewayClient|RestClient)\b/',
                $contents,
                $document . ' 仍引用重命名前的通道 Client',
            );
            self::assertDoesNotMatchRegularExpression('/We\\\{1,2}Client\b/', $contents, $document . ' 仍引用已删除的根 Client');
            self::assertDoesNotMatchRegularExpression(
                '/We\\\{1,2}Platform\\\{1,2}(?:Wechat|Alipay)\\\{1,2}\w+Client\b/',
                $contents,
                $document . ' 仍引用已删除的通道 Client 命名空间',
            );
            foreach ($removedTypes as $type) {
                self::assertStringNotContainsString($type, $contents, $document . ' 仍引用已删除类型 ' . $type);
            }
            foreach ($removedCommonTypes as $type) {
                self::assertStringNotContainsString($type, $contents, $document . ' 仍引用已迁移公共类型 ' . $type);
            }
            foreach ($removedConfigNamespaces as $namespace) {
                self::assertStringNotContainsString(
                    $namespace,
                    $contents,
                    $document . ' 仍引用已迁移 Config 命名空间 ' . $namespace,
                );
            }
            foreach ($removedNamespaceRoots as $namespace) {
                self::assertStringNotContainsString(
                    $namespace,
                    $contents,
                    $document . ' 仍引用已删除命名空间 ' . $namespace,
                );
            }
        }
    }

    private static function read(string $path): string
    {
        $contents = file_get_contents(self::root() . '/' . $path);
        self::assertIsString($contents, '无法读取文件：' . $path);

        return $contents;
    }

    /** @return list<string> */
    private static function markdownDocuments(): array
    {
        $paths = glob(self::root() . '/*.md') ?: [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root() . '/docs')) as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'md') {
                continue;
            }
            $paths[] = $file->getPathname();
        }
        $documents = array_map(
            static fn (string $path): string => substr($path, strlen(self::root()) + 1),
            $paths,
        );
        sort($documents);

        return $documents;
    }

    /** @return list<string> */
    private static function sourceFiles(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root() . '/src')) as $file) {
            if (!$file instanceof \SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            $files[] = substr($file->getPathname(), strlen(self::root()) + 1);
        }
        sort($files);

        return $files;
    }

    private static function typeDocComment(string $source): string
    {
        preg_match(
            '/(\/\*\*.*?\*\/)\s*(?:(?:abstract|final|readonly)\s+)*(?:class|interface|trait|enum)\s+\w+/s',
            $source,
            $matches,
        );

        return $matches[1] ?? '';
    }

    /** @return list<string> */
    private static function ecosystemInternalFiles(): array
    {
        $files = [];
        foreach (['Wechat', 'Alipay'] as $ecosystem) {
            $root = self::root() . '/src/' . $ecosystem;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
                if (
                    $file instanceof \SplFileInfo
                    && $file->isFile()
                    && $file->getExtension() === 'php'
                    && str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'Internal' . DIRECTORY_SEPARATOR)
                ) {
                    $files[] = $file->getPathname();
                }
            }
        }
        sort($files);

        return $files;
    }

    private static function root(): string
    {
        return dirname(__DIR__);
    }
}
