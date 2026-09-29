<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PropiedadService;

final class PropiedadController extends Controller
{
    public function __construct(private readonly PropiedadService $service)
    {
    }

    public function index(Request $request): Response
    {
        return $this->respond($this->service->listar());
    }

    public function show(Request $request, int $id): Response
    {
        return $this->respond($this->service->buscar($id));
    }

    public function store(Request $request): Response
    {
        return $this->respond($this->service->crear($request->all()), 201);
    }

    public function update(Request $request, int $id): Response
    {
        return $this->respond($this->service->actualizar($id, $request->all()));
    }

    public function destroy(Request $request, int $id): Response
    {
        $this->service->eliminar($id);

        return $this->noContent();
    }
}
