<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CategoriaRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select(
            'SELECT c.id, c.nombre, c.descripcion, c.activo, c.created_at, c.updated_at,
                    COUNT(cp.propiedad_id) AS propiedades_count
             FROM categorias c
             LEFT JOIN categoria_propiedad cp ON cp.categoria_id = c.id
             GROUP BY c.id, c.nombre, c.descripcion, c.activo, c.created_at, c.updated_at
             ORDER BY c.nombre ASC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT id, nombre, descripcion, activo, created_at, updated_at
             FROM categorias
             WHERE id = :id',
            ['id' => $id]
        );
    }

    public function findByNombre(string $nombre, ?int $exceptId = null): ?array
    {
        $sql = 'SELECT id, nombre FROM categorias WHERE nombre = :nombre';
        $params = ['nombre' => $nombre];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :except_id';
            $params['except_id'] = $exceptId;
        }

        return $this->db->selectOne($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO categorias (nombre, descripcion, activo)
             VALUES (:nombre, :descripcion, :activo)',
            [
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'activo' => (int) ($data['activo'] ?? true),
            ]
        );
    }

    public function update(int $id, array $data): int
    {
        return $this->db->affectedRows(
            'UPDATE categorias
             SET nombre = :nombre,
                 descripcion = :descripcion,
                 activo = :activo
             WHERE id = :id',
            [
                'id' => $id,
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? null,
                'activo' => (int) ($data['activo'] ?? true),
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->statement('DELETE FROM categorias WHERE id = :id', ['id' => $id]);
    }

    public function propiedadesIds(int $id): array
    {
        $rows = $this->db->select(
            'SELECT propiedad_id FROM categoria_propiedad WHERE categoria_id = :id ORDER BY propiedad_id',
            ['id' => $id]
        );

        return array_map(static fn (array $row): int => (int) $row['propiedad_id'], $rows);
    }
}
