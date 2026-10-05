<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'default' => Env::get('DB_CONNECTION', 'mysql'),

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => Env::get('DB_HOST', '127.0.0.1'),
            'port' => (int) Env::get('DB_PORT', '3306'),
            'database' => Env::get('DB_DATABASE', 'a0200336_inmo'),
            'username' => Env::get('DB_USERNAME', 'a0200336_inmo'),
            'password' => (string) Env::get('DB_PASSWORD', 'rugeGOno77'),
            'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
            'collation' => Env::get('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix' => Env::get('DB_PREFIX', ''),
            'options' => [],
        ],
    ],
];
