<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\NotFoundHttpException;
use App\Core\Exceptions\ValidationException;
use App\Core\Validator;
use App\Repositories\CategoriaRepository;

final class CategoriaService
{
    private const RULES = [
        'nombre' => 'required|string|max:120',
        'descripcion' => 'nullable|string|max:2000',
        'activo' => 'nullable|boolean',
    ];

    private const LABELS = [
        'nombre' => 'nombre',
        'descripcion' => 'descripción',
        'activo' => 'activo',
    ];

    public function __construct(private readonly CategoriaRepository $categorias)
    {
    }

    public function listar(): array
    {
        return $this->categorias->all();
    }

    public function buscar(int $id): array
    {
        $categoria = $this->categorias->find($id);

        if ($categoria === null) {
            throw new NotFoundHttpException("La categoría {$id} no existe.");
        }

        $categoria['activo'] = (bool) $categoria['activo'];
        $categoria['propiedades_ids'] = $this->categorias->propiedadesIds($id);

        return $categoria;
    }

    public function crear(array $datos): array
    {
        $validados = Validator::validate($datos, self::RULES, self::LABELS);

        $this->verificarNombreUnico($validados['nombre']);

        $id = $this->categorias->create($validados);

        return $this->buscar($id);
    }

    public function actualizar(int $id, array $datos): array
    {
        $actual = $this->buscar($id);
        $validados = Validator::validate($datos, self::RULES, self::LABELS);

        $nombre = $validados['nombre'] ?? $actual['nombre'];
        $this->verificarNombreUnico($nombre, $id);

        $this->categorias->update($id, [
            'nombre' => $nombre,
            'descripcion' => array_key_exists('descripcion', $validados) ? $validados['descripcion'] : $actual['descripcion'],
            'activo' => $validados['activo'] ?? $actual['activo'],
        ]);

        return $this->buscar($id);
    }

    public function eliminar(int $id): void
    {
        $this->buscar($id);
        $this->categorias->delete($id);
    }

    private function verificarNombreUnico(string $nombre, ?int $exceptId = null): void
    {
        if ($this->categorias->findByNombre($nombre, $exceptId) !== null) {
            throw new ValidationException(
                'Los datos enviados no son válidos.',
                ['nombre' => 'Ya existe una categoría con ese nombre.']
            );
        }
    }
}
