<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$documentRoot = realpath($basePath . '/web');

$app = require $basePath . '/bootstrap/app.php';

const CARPETAS_DE_API = ['api'];

$path = '/' . ltrim(
    (string) (
        rawurldecode(
            (string) (
                parse_url(
                    (string) ($_SERVER['REQUEST_URI'] ?? '/'),
                    PHP_URL_PATH
                ) ?: '/'
            )
        ) ?: '/'
    ),
    '/'
);

$segmentos = array_values(
    array_filter(
        explode('/', $path),
        static fn (string $s): bool => $s !== ''
    )
);

$archivo = basename($path);

/*
|--------------------------------------------------------------------------
| Servir imágenes desde storage/uploads
|--------------------------------------------------------------------------
|
| Las imágenes de propiedades se almacenan en:
|
| storage/uploads/propiedades/
|
| Pero públicamente se accede mediante:
|
| /uploads/propiedades/nombre.png
|
*/

if (
    PHP_SAPI === 'cli-server'
    && isset($segmentos[0])
    && strtolower($segmentos[0]) === 'uploads'
) {
    $rutaRelativa = implode(
        DIRECTORY_SEPARATOR,
        array_slice($segmentos, 1)
    );

    $storageRoot = realpath($basePath . '/storage/uploads');

    if ($storageRoot !== false && $rutaRelativa !== '') {
        $candidate = realpath(
            $storageRoot . DIRECTORY_SEPARATOR . $rutaRelativa
        );

        if (
            $candidate !== false
            && is_file($candidate)
            && str_starts_with(
                $candidate,
                $storageRoot . DIRECTORY_SEPARATOR
            )
        ) {
            $mime = mime_content_type($candidate) ?: 'application/octet-stream';

            header('Content-Type: ' . $mime);
            header('Content-Length: ' . (string) filesize($candidate));
            header('Cache-Control: public, max-age=86400');

            readfile($candidate);
            exit;
        }
    }

    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'NOT_FOUND',
            'message' => 'Archivo no encontrado.',
        ],
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Archivos públicos normales
|--------------------------------------------------------------------------
*/

$archivoOculto =
    str_starts_with($archivo, '.')
    || $archivo === 'dispatch.php';

$carpetaOculta = in_array(
    strtolower($segmentos[0] ?? ''),
    array_merge(CARPETAS_DE_API, [
        'bin',
        'bootstrap',
        'config',
        'database',
        'src',
        'storage',
        'tests',
        'vendor',
    ]),
    true
);

if (
    !$archivoOculto
    && !$carpetaOculta
    && PHP_SAPI === 'cli-server'
    && $documentRoot !== false
) {
    $candidate = realpath($documentRoot . $path);

    if (
        $candidate !== false
        && is_file($candidate)
        && str_starts_with(
            $candidate,
            $documentRoot . DIRECTORY_SEPARATOR
        )
    ) {
        return false;
    }
}

$app->run(App\Core\Request::capture());