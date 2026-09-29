<?php

declare(strict_types=1);

use App\Controllers\CategoriaController;
use App\Controllers\HealthController;
use App\Controllers\PropiedadController;
use App\Core\Router;

return function (Router $router): void {
    $router->get('/api', [HealthController::class, 'index']);
    $router->get('/api/health', [HealthController::class, 'health']);
    $router->get('/api/health/database', [HealthController::class, 'database']);

    $router->get('/api/categorias', [CategoriaController::class, 'index']);
    $router->get('/api/categorias/{id}', [CategoriaController::class, 'show']);
    $router->post('/api/categorias', [CategoriaController::class, 'store']);
    $router->put('/api/categorias/{id}', [CategoriaController::class, 'update']);
    $router->delete('/api/categorias/{id}', [CategoriaController::class, 'destroy']);

    $router->get('/api/propiedades', [PropiedadController::class, 'index']);
    $router->get('/api/propiedades/{id}', [PropiedadController::class, 'show']);
    $router->post('/api/propiedades', [PropiedadController::class, 'store']);
    $router->put('/api/propiedades/{id}', [PropiedadController::class, 'update']);
    $router->delete('/api/propiedades/{id}', [PropiedadController::class, 'destroy']);
};
