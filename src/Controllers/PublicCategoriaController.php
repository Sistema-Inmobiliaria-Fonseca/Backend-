<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\CategoriaService;

/**
 * Catalogo publico de categorias para la Landing. Mismo motivo que
 * `PublicPropiedadController`: el sitio abierto no manda token.
 */
final class PublicCategoriaController extends Controller
{
    public function __construct(private readonly CategoriaService $service)
    {
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->listar(), 200, [
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}