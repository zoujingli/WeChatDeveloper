<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

$config = new Config();
$config->setRiskyAllowed(true)->setParallelConfig(new ParallelConfig(8, 24));
$finder = Finder::create()
    ->files()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->append([__FILE__]);

return $config->setFinder($finder)->setUsingCache(false)->setRules([
    '@PSR2' => true,
    '@Symfony' => true,
    '@DoctrineAnnotation' => true,
    '@PhpCsFixer' => true,
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'list_syntax' => [
        'syntax' => 'short',
    ],
    'blank_line_before_statement' => [
        'statements' => [
            'declare',
        ],
    ],
    'general_phpdoc_annotation_remove' => [
        'annotations' => [
            'author',
        ],
    ],
    'ordered_imports' => [
        'imports_order' => [
            'class', 'function', 'const',
        ],
        'sort_algorithm' => 'alpha',
    ],
    'ordered_types' => [
        'null_adjustment' => 'always_last',
        'sort_algorithm' => 'alpha',
    ],
    'single_line_comment_style' => [
        'comment_types' => [],
    ],
    'yoda_style' => [
        'always_move_variable' => false,
        'equal' => false,
        'identical' => false,
    ],
    'phpdoc_align' => [
        'align' => 'left',
    ],
    'multiline_whitespace_before_semicolons' => [
        'strategy' => 'no_multi_line',
    ],
    'use_arrow_functions' => true,
    'constant_case' => [
        'case' => 'lower',
    ],
    'encoding' => true, // PHP代码必须只使用没有BOM的UTF-8
    'line_ending' => true, // 所有的PHP文件编码必须一致
    'single_quote' => true, // 简单字符串应该使用单引号代替双引号
    'no_empty_statement' => true, // 不应该存在空的结构体
    'standardize_not_equals' => true, // 使用 <> 代替 !=
    'blank_line_after_namespace' => true, // 命名空间之后空一行
    'no_empty_phpdoc' => true, // 不应该存在空的 phpdoc
    'no_empty_comment' => true, // 不应该存在空注释
    'no_singleline_whitespace_before_semicolons' => true, // 禁止在关闭分号前使用单行空格
    'concat_space' => ['spacing' => 'one'], // 连接字符是否需要空格,可选配置项 none:不需要 one:一个空格
    'no_leading_import_slash' => true, // use 语句中取消前置斜杠
    'cast_spaces' => ['space' => 'none'],
    'class_attributes_separation' => true,
    'combine_consecutive_unsets' => true,
    'declare_strict_types' => true,
    'lowercase_static_reference' => true,
    'linebreak_after_opening_tag' => true,
    'multiline_comment_opening_closing' => false,
    'no_useless_else' => true,
    'no_unused_imports' => true,
    'not_operator_with_successor_space' => false,
    'not_operator_with_space' => false,
    'ordered_class_elements' => true,
    'php_unit_strict' => false,
    'phpdoc_separation' => false,
    'phpdoc_to_comment' => false,
]);
