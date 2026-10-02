<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\GeografiaController;
use App\Controllers\HealthController;
use App\Controllers\PropiedadController;
use App\Controllers\PropiedadImagenController;
use App\Core\Router;
use App\Middleware\AuthenticateMiddleware;

return function (Router $router): void {
    // Todo lo que no sea /api/health requiere un token Bearer valido.
    $auth = [AuthenticateMiddleware::class];

    $router->get('/api', [HealthController::class, 'index']);
    $router->get('/api/health', [HealthController::class, 'health']);
    $router->get('/api/health/database', [HealthController::class, 'database']);

    $router->post('/api/auth/login', [AuthController::class, 'login']);
    $router->get('/api/auth/me', [AuthController::class, 'me'], $auth);

    $router->get('/api/categorias', [CategoriaController::class, 'index'], $auth);
    $router->get('/api/categorias/{id}', [CategoriaController::class, 'show'], $auth);
    $router->post('/api/categorias', [CategoriaController::class, 'store'], $auth);
    $router->put('/api/categorias/{id}', [CategoriaController::class, 'update'], $auth);
    $router->delete('/api/categorias/{id}', [CategoriaController::class, 'destroy'], $auth);

    $router->get('/api/propiedades', [PropiedadController::class, 'index'], $auth);
    $router->get('/api/propiedades/{id}', [PropiedadController::class, 'show'], $auth);
    $router->post('/api/propiedades', [PropiedadController::class, 'store'], $auth);
    $router->put('/api/propiedades/{id}', [PropiedadController::class, 'update'], $auth);
    $router->delete('/api/propiedades/{id}', [PropiedadController::class, 'destroy'], $auth);

    // Imagenes de la propiedad. El orden arranca en 1 y la de orden 1 es la principal.
    $router->get('/api/propiedades/{id}/imagenes', [PropiedadImagenController::class, 'index'], $auth);
    $router->post('/api/propiedades/{id}/imagenes', [PropiedadImagenController::class, 'store'], $auth);
    $router->put('/api/propiedades/{id}/imagenes/orden', [PropiedadImagenController::class, 'updateOrder'], $auth);
    $router->delete('/api/propiedades/{id}/imagenes/{imagenId}', [PropiedadImagenController::class, 'destroy'], $auth);

    // Servicio del archivo. Sin token: las fotos van en el <img> del sitio, que no
    // puede mandar cabeceras. El nombre se valida contra el patron que genera la API.
    $router->get('/uploads/propiedades/{nombre}', [PropiedadImagenController::class, 'show']);

    // Catalogo geografico: solo consulta. Los datos se cargan con database/seeds.
    $router->get('/api/paises', [GeografiaController::class, 'paises'], $auth);
    $router->get('/api/paises/{id}', [GeografiaController::class, 'pais'], $auth);
    $router->get('/api/provincias', [GeografiaController::class, 'provincias'], $auth);
    $router->get('/api/provincias/{id}', [GeografiaController::class, 'provincia'], $auth);
    $router->get('/api/localidades', [GeografiaController::class, 'localidades'], $auth);
    $router->get('/api/localidades/{id}', [GeografiaController::class, 'localidad'], $auth);
};