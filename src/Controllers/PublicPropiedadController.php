<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PropiedadService;

/**
 * Lectura publica de propiedades para el sitio de la Landing.
 *
 * Es separada de `PropiedadController` a proposito: el panel necesita token para
 * todo, y estas rutas no lo piden porque las consume un sitio abierto que no
 * tiene sesion. Solo expone GET; escribir sigue pasando por el panel con token.
 *
 * Reutiliza los mismos services, asi que la forma de los datos no se duplica.
 */
final class PublicPropiedadController extends Controller
{
    public function __construct(private readonly PropiedadService $service)
    {
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->listar(), 200, $this->cacheHeaders());
    }

    public function show(Request $request, int $id): Response
    {
        return $this->respond($this->service->buscar($id), 200, $this->cacheHeaders());
    }

    /**
     * El listado publico se puede cachear en el navegador y en proxies: es el
     * mismo dato para todos y no depende de quien lo pida.
     */
    private function cacheHeaders(): array
    {
        return ['Cache-Control' => 'public, max-age=60'];
    }
}