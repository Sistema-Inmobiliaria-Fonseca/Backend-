<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$documentRoot = realpath($basePath . '/web');

$app = require $basePath . '/bootstrap/app.php';

const CARPETAS_DE_API = ['api'];

$path = '/' . ltrim((string) (rawurldecode((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/')) ?: '/'), '/');
$segmentos = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));
$archivo = basename($path);

$archivoOculto = str_starts_with($archivo, '.') || $archivo === 'dispatch.php';
$carpetaOculta = in_array(strtolower($segmentos[0] ?? ''), array_merge(CARPETAS_DE_API, [
    'bin',
    'bootstrap',
    'config',
    'database',
    'src',
    'storage',
    'tests',
    'vendor',
]), true);

if (!$archivoOculto && !$carpetaOculta && PHP_SAPI === 'cli-server' && $documentRoot !== false) {
    $candidate = realpath($documentRoot . $path);

    if (
        $candidate !== false
        && is_file($candidate)
        && str_starts_with($candidate, $documentRoot . DIRECTORY_SEPARATOR)
    ) {
        return false;
    }
}

$app->run(App\Core\Request::capture());