<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\PropiedadImagenService;

final class PropiedadImagenController extends Controller
{
    public function __construct(private readonly PropiedadImagenService $service)
    {
    }

    public function index(Request $request, int $id): Response
    {
        return $this->respond($this->service->listar($id));
    }

    public function store(Request $request, int $id): Response
    {
        $file = $request->file('imagen');

        if ($file === null) {
            return Response::json([
                'success' => false,
                'error' => [
                    'code' => 422,
                    'message' => 'No se pudo subir la imagen.',
                    'details' => [
                        'imagen' => 'Enviá la imagen en el campo multipart imagen.',
                    ],
                ],
            ], 422);
        }

        return $this->respond($this->service->agregar($id, $file), 201);
    }

    public function updateOrder(Request $request, int $id): Response
    {
        return $this->respond($this->service->ordenar($id, $this->idsDelRequest($request)));
    }

    public function destroy(Request $request, int $id, int $imagenId): Response
    {
        return $this->respond($this->service->eliminar($id, $imagenId));
    }

    /**
     * Ruta publica del archivo. No pide token porque las fotos van en el <img>
     * de la web, que no puede mandar cabeceras; el archivo igual se valida contra
     * el patron de nombres que genera la API.
     */
    public function show(Request $request, string $nombre): Response
    {
        $archivo = $this->service->contenidoPublico($nombre);
        $cacheMaxAge = (int) $this->service->maxAgeCache();

        return new Response($archivo['contenido'], 200, [
            'Content-Type' => $archivo['mime'],
            'Content-Length' => (string) strlen($archivo['contenido']),
            'Content-Disposition' => 'inline; filename="' . $archivo['nombre'] . '"',
            'Cache-Control' => 'public, max-age=' . $cacheMaxAge,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Acepta [3,1,2] o {"imagenes":[3,1,2]} / {"ids":[3,1,2]}.
     */
    private function idsDelRequest(Request $request): mixed
    {
        $cuerpo = $request->all();

        if (array_key_exists('imagenes', $cuerpo)) {
            return $cuerpo['imagenes'];
        }

        if (array_key_exists('ids', $cuerpo)) {
            return $cuerpo['ids'];
        }

        return $cuerpo;
    }
}