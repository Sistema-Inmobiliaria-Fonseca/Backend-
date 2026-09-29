<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Config;
use App\Core\Container;
use App\Core\Database;
use App\Core\Env;
use App\Middleware\CorsMiddleware;
use App\Middleware\ErrorHandlerMiddleware;

$basePath = dirname(__DIR__);

require $basePath . '/bootstrap/autoload.php';

Env::load($basePath . '/.env');

$config = new Config($basePath . '/config');

date_default_timezone_set((string) $config->get('app.timezone', 'UTC'));

$container = new Container();
$container->instance(Config::class, $config);
$container->instance(Database::class, new Database((array) $config->get('database', [])));

$app = new App($container);

$app->use(new CorsMiddleware(
    (array) $config->get('app.cors.allowed_origins', ['*']),
    (string) $config->get('app.cors.allowed_methods', 'GET,POST,PUT,PATCH,DELETE,OPTIONS'),
    (string) $config->get('app.cors.allowed_headers', 'Content-Type,Authorization,X-Requested-With'),
    (int) $config->get('app.cors.max_age', 86400),
    (bool) $config->get('app.cors.supports_credentials', false)
));

$app->use(new ErrorHandlerMiddleware(
    (bool) $config->get('app.debug', false),
    $config->get('app.log_file')
));

$registerRoutes = require $basePath . '/routes/api.php';
$registerRoutes($app->router());

return $app;
