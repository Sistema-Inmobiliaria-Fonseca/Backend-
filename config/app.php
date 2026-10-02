<?php

declare(strict_types=1);

use App\Core\Env;

$root = dirname(__DIR__);

return [
    'name' => Env::get('APP_NAME', 'Inmobiliaria API'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => (bool) Env::get('APP_DEBUG', false),
    'url' => rtrim((string) Env::get('APP_URL', ''), '/'),
    'version' => Env::get('APP_VERSION', '0.1.0'),
    'timezone' => Env::get('APP_TIMEZONE', 'UTC'),
    'log_file' => $root . '/storage/logs/app.log',
    'auth' => [
        'token_secret' => Env::get('AUTH_TOKEN_SECRET', ''),
        'token_ttl' => (int) Env::get('AUTH_TOKEN_TTL', '43200'),
    ],
'cors' => [
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) Env::get('CORS_ALLOWED_ORIGINS', '*'))
        ))),
        'allowed_methods' => Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'allowed_headers' => Env::get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With'),
        'max_age' => (int) Env::get('CORS_MAX_AGE', 86400),
        'supports_credentials' => (bool) Env::get('CORS_SUPPORTS_CREDENTIALS', false),
    ],
    'uploads' => [
        // Carpeta fisica, fuera del document root: el navegador no la ve directamente.
        'path' => (($uploadPath = trim((string) Env::get('UPLOAD_PATH', ''))) !== '')
            ? $uploadPath
            : $root . '/storage/uploads/propiedades',
        // Ruta publica por la que la API sirve los archivos.
        'public_path' => '/uploads/propiedades',
        // Tope de imagenes por propiedad.
        'max_images' => (int) Env::get('UPLOAD_MAX_IMAGES', '12'),
        // Tope de bytes por imagen. 0 = el que impongan upload_max_filesize/post_max_size.
        'max_bytes' => (int) Env::get('UPLOAD_MAX_BYTES', '0'),
        // mime real => extension generada. La extension nunca sale del nombre enviado.
        'allowed_mimes' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ],
        'max_age' => (int) Env::get('UPLOAD_CACHE_MAX_AGE', '604800'),
    ],
];
