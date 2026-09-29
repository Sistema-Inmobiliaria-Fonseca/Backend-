<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class CategoriaPropiedadRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function sync(int $propiedadId, array $categoriaIds): void
    {
        $this->db->statement(
            'DELETE FROM categoria_propiedad WHERE propiedad_id = :propiedad_id',
            ['propiedad_id' => $propiedadId]
        );

        foreach ($categoriaIds as $categoriaId) {
            $this->db->statement(
                'INSERT INTO categoria_propiedad (categoria_id, propiedad_id)
                 VALUES (:categoria_id, :propiedad_id)
                 ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP',
                ['categoria_id' => $categoriaId, 'propiedad_id' => $propiedadId]
            );
        }
    }

    public function categoriasDe(int $propiedadId): array
    {
        return $this->db->select(
            'SELECT c.id, c.nombre
             FROM categoria_propiedad cp
             INNER JOIN categorias c ON c.id = cp.categoria_id
             WHERE cp.propiedad_id = :propiedad_id
             ORDER BY c.nombre ASC',
            ['propiedad_id' => $propiedadId]
        );
    }

    public function categoriasDeTodas(): array
    {
        return $this->db->select(
            'SELECT cp.propiedad_id, c.id, c.nombre
             FROM categoria_propiedad cp
             INNER JOIN categorias c ON c.id = cp.categoria_id
             ORDER BY cp.propiedad_id ASC, c.nombre ASC'
        );
    }

    public function idsExistentes(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $rows = $this->db->select(
            "SELECT id FROM categorias WHERE id IN ({$placeholders})",
            array_values($ids)
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
