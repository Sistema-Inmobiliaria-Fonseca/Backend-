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
    'cors' => [
        'allowed_origins' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) Env::get('CORS_ALLOWED_ORIGINS', '*'))
        ))),
        'allowed_methods' => Env::get('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
        'allowed_headers' => Env::get('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With'),
        'max_age' => (int) Env::get('CORS_MAX_AGE', '86400'),
        'supports_credentials' => (bool) Env::get('CORS_SUPPORTS_CREDENTIALS', false),
    ],
];
