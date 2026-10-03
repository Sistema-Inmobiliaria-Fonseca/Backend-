<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Exceptions\ValidationException;
use App\Core\Validator;
use App\Repositories\CategoriaPropiedadRepository;
use App\Repositories\LocalidadRepository;
use App\Repositories\PropiedadRepository;

final class PropiedadService
{
    private const RULES = [
        'nombre' => 'required|string|max:200',
        'localidad_id' => 'nullable|integer|min:1',
        'metros_cuadrados' => 'nullable|numeric|min:0',
        'valor' => 'nullable|numeric|min:0',
        'cantidad_habitaciones' => 'nullable|integer|min:0',
        'cantidad_ambientes' => 'nullable|integer|min:0',
        'descripcion' => 'nullable|string|max:5000',
        'apto_credito' => 'nullable|boolean',
        'estado' => 'nullable|in:disponible,alquilada,vendida',
        'categorias' => 'nullable|array',
    ];

    private const LABELS = [
        'nombre' => 'nombre',
        'localidad_id' => 'id de localidad',
        'metros_cuadrados' => 'metros cuadrados',
        'valor' => 'valor',
        'cantidad_habitaciones' => 'cantidad de habitaciones',
        'cantidad_ambientes' => 'cantidad de ambientes',
        'descripcion' => 'descripción',
        'apto_credito' => 'apto a crédito',
        'estado' => 'estado',
        'categorias' => 'categorías',
    ];

    public function __construct(
        private readonly Database $db,
        private readonly PropiedadRepository $propiedades,
        private readonly CategoriaPropiedadRepository $pivot,
        private readonly LocalidadRepository $localidades,
        private readonly PropiedadImagenService $imagenes
    ) {
    }

    public function listar(): array
    {
        $propiedades = $this->conCategorias($this->propiedades->all());
        foreach ($propiedades as &$propiedad) {
            $propiedad["imagenes"] = $this->imagenes->listar((int) $propiedad["id"]);
        }
        unset($propiedad);
        return $propiedades;
    }
    public function buscar(int $id): array
    {
        $propiedad = $this->formatear($this->propiedades->find($id));

        if ($propiedad === null) {
            throw new NotFoundHttpException("La propiedad {$id} no existe.");
        }

        $propiedad['categorias'] = $this->pivot->categoriasDe($id);
        $propiedad['imagenes'] = $this->imagenes->listar($id);

        return $propiedad;
    }
    public function crear(array $datos): array
    {
        $validados = Validator::validate($datos, self::RULES, self::LABELS);
        $categoriaIds = $this->validarCategorias($validados['categorias'] ?? []);
        $localidadId = $this->validarLocalidad($validados['localidad_id'] ?? null);

        $id = $this->db->transaction(function () use ($validados, $categoriaIds, $localidadId): int {
            $propiedadId = $this->propiedades->create($validados + ['localidad_id' => $localidadId]);
            $this->pivot->sync($propiedadId, $categoriaIds);

            return $propiedadId;
        });

        return $this->buscar($id);
    }

    public function actualizar(int $id, array $datos): array
    {
        $actual = $this->buscar($id);
        $validados = Validator::validate($datos, self::RULES, self::LABELS);
        $categoriaIds = $this->validarCategorias($validados['categorias'] ?? []);

        $this->db->transaction(function () use ($id, $actual, $datos, $validados, $categoriaIds): void {
            $this->propiedades->update($id, [
                'nombre' => $validados['nombre'] ?? $actual['nombre'],
                'localidad_id' => array_key_exists('localidad_id', $datos)
                    ? $this->validarLocalidad($validados['localidad_id'] ?? null)
                    : $actual['localidad_id'],
                'metros_cuadrados' => $validados['metros_cuadrados'] ?? $actual['metros_cuadrados'],
                'valor' => $validados['valor'] ?? $actual['valor'],
                'cantidad_habitaciones' => $validados['cantidad_habitaciones'] ?? $actual['cantidad_habitaciones'],
                'cantidad_ambientes' => $validados['cantidad_ambientes'] ?? $actual['cantidad_ambientes'],
                'descripcion' => array_key_exists('descripcion', $validados) ? $validados['descripcion'] : $actual['descripcion'],
                'apto_credito' => $validados['apto_credito'] ?? $actual['apto_credito'],
                'estado' => $validados['estado'] ?? $actual['estado'],
            ]);

            if (array_key_exists('categorias', $validados)) {
                $this->pivot->sync($id, $categoriaIds);
            }
        });

        return $this->buscar($id);
    }

    public function eliminar(int $id): void
    {
        $this->buscar($id);

        // Los archivos se borran antes del DELETE: la cascada de la base se lleva
        // las filas, pero el disco hay que limpiarlo a mano.
        $this->imagenes->eliminarArchivosDePropiedad($id);
        $this->propiedades->delete($id);
    }

    private function validarCategorias(mixed $categorias): array
    {
        if (!is_array($categorias)) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'categorias' => 'El campo categorías debe ser una lista de ids.',
            ]);
        }

        $ids = [];

        foreach ($categorias as $categoria) {
            $id = filter_var($categoria, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($id === false) {
                throw new ValidationException('Los datos enviados no son válidos.', [
                    'categorias' => 'El campo categorías debe contener solo ids numéricos.',
                ]);
            }

            $ids[] = $id;
        }

        $ids = array_values(array_unique($ids));

        $existentes = $this->pivot->idsExistentes($ids);
        $inexistentes = array_values(array_diff($ids, $existentes));

        if ($inexistentes !== []) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'categorias' => 'Las categorías no existen: ' . implode(', ', $inexistentes) . '.',
            ]);
        }

        return $ids;
    }

    private function validarLocalidad(mixed $localidadId): ?int
    {
        if ($localidadId === null) {
            return null;
        }

        $id = (int) $localidadId;

        if ($this->localidades->findByIds([$id]) === []) {
            throw new ValidationException('Los datos enviados no son válidos.', [
                'localidad_id' => "La localidad {$id} no existe en el catálogo geográfico.",
            ]);
        }

        return $id;
    }

    private function conCategorias(array $propiedades): array
    {
        $porPropiedad = [];

        foreach ($this->pivot->categoriasDeTodas() as $fila) {
            $porPropiedad[(int) $fila['propiedad_id']][] = [
                'id' => (int) $fila['id'],
                'nombre' => $fila['nombre'],
            ];
        }

        // Una sola consulta para las imágenes de todas las propiedades del listado.
        $imagenesPorPropiedad = $this->imagenes->listarVarias(
            array_map(static fn (array $fila): int => (int) $fila['id'], $propiedades)
        );

        return array_map(function (array $propiedad) use ($porPropiedad, $imagenesPorPropiedad): array {
            $propiedad = $this->formatear($propiedad);
            $propiedad['categorias'] = $porPropiedad[$propiedad['id']] ?? [];
            $propiedad['imagenes'] = $imagenesPorPropiedad[$propiedad['id']] ?? [];

            return $propiedad;
        }, $propiedades);
    }

    private function formatear(?array $propiedad): ?array
    {
        if ($propiedad === null) {
            return null;
        }

        $propiedad['id'] = (int) $propiedad['id'];
        $propiedad['localidad_id'] = $propiedad['localidad_id'] !== null
            ? (int) $propiedad['localidad_id']
            : null;
        $propiedad['metros_cuadrados'] = $propiedad['metros_cuadrados'] !== null
            ? (float) $propiedad['metros_cuadrados']
            : null;
        $propiedad['valor'] = $propiedad['valor'] !== null ? (float) $propiedad['valor'] : null;
        $propiedad['cantidad_habitaciones'] = (int) $propiedad['cantidad_habitaciones'];
        $propiedad['cantidad_ambientes'] = (int) $propiedad['cantidad_ambientes'];
        $propiedad['apto_credito'] = (bool) $propiedad['apto_credito'];
        $propiedad['descripcion'] = $propiedad['descripcion'] !== null
            ? (string) $propiedad['descripcion']
            : null;
        $propiedad['ubicacion'] = $this->formatearUbicacion($propiedad);

        foreach (['localidad_nombre', 'provincia_id', 'provincia_nombre', 'pais_id', 'pais_nombre', 'pais_codigo_iso'] as $columna) {
            unset($propiedad[$columna]);
        }

        return $propiedad;
    }

    /**
     *Arma el árbol País -> Provincia -> Localidad a partir del JOIN del repositorio.
     */
    private function formatearUbicacion(array $propiedad): ?array
    {
        if ($propiedad['localidad_id'] === null) {
            return null;
        }

        return [
            'localidad' => [
                'id' => $propiedad['localidad_id'],
                'nombre' => $propiedad['localidad_nombre'],
            ],
            'provincia' => [
                'id' => (int) $propiedad['provincia_id'],
                'nombre' => $propiedad['provincia_nombre'],
            ],
            'pais' => [
                'id' => (int) $propiedad['pais_id'],
                'nombre' => $propiedad['pais_nombre'],
                'codigo_iso' => $propiedad['pais_codigo_iso'],
            ],
        ];
    }
}
