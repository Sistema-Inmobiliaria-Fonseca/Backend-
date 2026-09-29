<?php

declare(strict_types=1);

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require $vendorAutoload;

    return;
}

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\' => dirname(__DIR__) . '/src' . DIRECTORY_SEPARATOR,
        'Tests\\' => dirname(__DIR__) . '/tests' . DIRECTORY_SEPARATOR,
    ];

    foreach ($prefixes as $prefix => $directory) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = substr($class, strlen($prefix));
        $file = $directory . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

        if (is_file($file)) {
            require $file;

            return;
        }
    }
});
