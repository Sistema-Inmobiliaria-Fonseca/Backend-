<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\GeografiaService;

/**
 * Solo consulta del catálogo geográfico. No expone POST, PUT ni DELETE:
 * los países, provincias y localidades se cargan con seeds.
 */
final class GeografiaController extends Controller
{
    public function __construct(private readonly GeografiaService $service)
    {
    }

    public function paises(Request $request): Response
    {
        return $this->respond($this->service->paises());
    }

    public function pais(Request $request, int $id): Response
    {
        return $this->respond($this->service->pais($id));
    }

    public function provincias(Request $request): Response
    {
        $paisId = $this->service->paisIdDesdeQuery($request->query());

        return $this->respond($this->service->provincias($paisId));
    }

    public function provincia(Request $request, int $id): Response
    {
        return $this->respond($this->service->provincia($id));
    }

    public function localidades(Request $request): Response
    {
        $provinciaId = $this->service->provinciaIdDesdeQuery($request->query());

        return $this->respond($this->service->localidades($provinciaId));
    }

    public function localidad(Request $request, int $id): Response
    {
        return $this->respond($this->service->localidad($id));
    }
}
