<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Validator;
use App\Repositories\LocalidadRepository;
use App\Repositories\PaisRepository;
use App\Repositories\ProvinciaRepository;

/**
 * Catálogo geográfico (País -> Provincia -> Localidad).
 *
 * Es un catálogo precargado por seed: acá no hay creación, edición ni borrado,
 * solo consultas para alimentar los selectores dependientes del administrador.
 */
final class GeografiaService
{
    public function __construct(
        private readonly PaisRepository $paises,
        private readonly ProvinciaRepository $provincias,
        private readonly LocalidadRepository $localidades
    ) {
    }

    public function paises(): array
    {
        return array_map([$this, 'formatearPais'], $this->paises->all());
    }

    public function pais(int $id): array
    {
        $pais = $this->paises->find($id);

        if ($pais === null) {
            throw new NotFoundHttpException("El país {$id} no existe.");
        }

        return $this->formatearPais($pais);
    }

    public function provincias(?int $paisId): array
    {
        return array_map([$this, 'formatearProvincia'], $this->provincias->all($paisId));
    }

    public function provincia(int $id): array
    {
        $provincia = $this->provincias->find($id);

        if ($provincia === null) {
            throw new NotFoundHttpException("La provincia {$id} no existe.");
        }

        $provincia = $this->formatearProvincia($provincia);
        $provincia['localidades'] = array_map(
            static fn (array $localidad): array => [
                'id' => (int) $localidad['id'],
                'nombre' => $localidad['nombre'],
            ],
            $this->provincias->localidades($id)
        );

        return $provincia;
    }

    public function localidades(?int $provinciaId): array
    {
        return array_map([$this, 'formatearLocalidad'], $this->localidades->all($provinciaId));
    }

    public function localidad(int $id): array
    {
        $localidad = $this->localidades->find($id);

        if ($localidad === null) {
            throw new NotFoundHttpException("La localidad {$id} no existe.");
        }

        $localidad = $this->formatearLocalidad($localidad);
        $localidad['propiedades_ids'] = $this->localidades->propiedadesIds($id);

        return $localidad;
    }

    public function existeLocalidad(int $id): bool
    {
        return $this->localidades->findByIds([$id]) !== [];
    }

    /**
     * Valida el filtro `pais_id` de la lista de provincias.
     */
    public function paisIdDesdeQuery(array $query): ?int
    {
        $validados = Validator::validate(
            $query,
            ['pais_id' => 'nullable|integer|min:1'],
            ['pais_id' => 'id de país']
        );

        return $validados['pais_id'] ?? null;
    }

    /**
     * Valida el filtro `provincia_id` de la lista de localidades.
     */
    public function provinciaIdDesdeQuery(array $query): ?int
    {
        $validados = Validator::validate(
            $query,
            ['provincia_id' => 'nullable|integer|min:1'],
            ['provincia_id' => 'id de provincia']
        );

        return $validados['provincia_id'] ?? null;
    }

    private function formatearPais(array $pais): array
    {
        $pais['id'] = (int) $pais['id'];
        $pais['activo'] = (bool) $pais['activo'];
        $pais['provincias_count'] = (int) $pais['provincias_count'];
        $pais['localidades_count'] = (int) $pais['localidades_count'];

        return $pais;
    }

    private function formatearProvincia(array $provincia): array
    {
        $provincia['id'] = (int) $provincia['id'];
        $provincia['pais_id'] = (int) $provincia['pais_id'];
        $provincia['activo'] = (bool) $provincia['activo'];
        $provincia['localidades_count'] = (int) $provincia['localidades_count'];

        return $provincia;
    }

    private function formatearLocalidad(array $localidad): array
    {
        $localidad['id'] = (int) $localidad['id'];
        $localidad['provincia_id'] = (int) $localidad['provincia_id'];
        $localidad['pais_id'] = (int) $localidad['pais_id'];
        $localidad['activo'] = (bool) $localidad['activo'];

        return $localidad;
    }
}
