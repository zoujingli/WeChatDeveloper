<?php

declare(strict_types=1);

$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    dirname(__DIR__, 3) . '/vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoloadFile) {
    if (is_file($autoloadFile)) {
        require $autoloadFile;
        spl_autoload_register(static function (string $class): void {
            $prefix = 'We\\';
            if (str_starts_with($class, $prefix)) {
                $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($path)) {
                    require $path;
                }
            }
        });
        return;
    }
}

throw new RuntimeException('无法找到 We 测试所需的 Composer autoload.php。');
