<?php

declare(strict_types=1);

/**
 * Front controller de la API: unico punto de entrada de todas las peticiones.
 *
 * Vive fuera del Document Root a proposito. Se usa de dos formas:
 *
 *   1) Servidor embebido de PHP (desde la raiz del proyecto):
 *
 *        php -S localhost:8000 -t web bootstrap/server.php
 *
 *   2) Apache/Laragon: el Document Root es la carpeta web/ y web/entry.php
 *      se limita a delegar en este archivo:
 *
 *        <?php
 *        return require dirname(__DIR__) . '/bootstrap/server.php';
 */

use App\Core\App;
use App\Core\Response;

$basePath = dirname(__DIR__);
$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$path = rawurldecode($path);
$segmentos = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));

$directoriosProtegidos = ['src', 'config', 'bootstrap', 'database', 'storage', 'tests', 'bin', 'vendor', 'web'];
$protegido = isset($segmentos[0])
    && (in_array($segmentos[0], $directoriosProtegidos, true) || str_starts_with($segmentos[0], '.'));

// Sirve los archivos reales del Document Root y corta cualquier intento de salir
// de la carpeta web (por ejemplo /../.env).
$documentRoot = $basePath . DIRECTORY_SEPARATOR . 'web';
$archivo = $protegido ? false : realpath($documentRoot . $path);
$archivoInterno = $archivo !== false
    && is_file($archivo)
    && str_starts_with($archivo, $documentRoot . DIRECTORY_SEPARATOR)
    && basename($archivo) !== 'entry.php';

if ($archivoInterno) {
    return false;
}

require $basePath . '/bootstrap/autoload.php';

/** @var App $app */
$app = require $basePath . '/bootstrap/app.php';

try {
    $app->run();
} catch (Throwable) {
    // Red de seguridad: si el fallo ocurrio antes de que el middleware de
    // errores tomara el control, la respuesta sigue siendo JSON.
    Response::json([
        'success' => false,
        'error' => [
            'code' => 500,
            'message' => 'Error interno del servidor.',
        ],
    ], 500)->send();
}