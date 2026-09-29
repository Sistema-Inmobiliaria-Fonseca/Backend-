<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Catálogo geográfico: las localidades se cargan por seed y solo se consultan.
 */
final class LocalidadRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(?int $provinciaId = null): array
    {
        $sql = 'SELECT lo.id, lo.provincia_id, lo.nombre, lo.activo,
                       pr.nombre AS provincia_nombre, pr.codigo AS provincia_codigo,
                       pa.id AS pais_id, pa.nombre AS pais_nombre, pa.codigo_iso AS pais_codigo_iso
                FROM localidades lo
                JOIN provincias pr ON pr.id = lo.provincia_id
                JOIN paises pa ON pa.id = pr.pais_id
                WHERE lo.activo = 1 AND pr.activo = 1 AND pa.activo = 1';

        $params = [];

        if ($provinciaId !== null) {
            $sql .= ' AND lo.provincia_id = :provincia_id';
            $params['provincia_id'] = $provinciaId;
        }

        return $this->db->select(
            $sql . ' ORDER BY pa.nombre ASC, pr.nombre ASC, lo.nombre ASC',
            $params
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT lo.id, lo.provincia_id, lo.nombre, lo.activo,
                    pr.nombre AS provincia_nombre, pr.codigo AS provincia_codigo,
                    pa.id AS pais_id, pa.nombre AS pais_nombre, pa.codigo_iso AS pais_codigo_iso
             FROM localidades lo
             JOIN provincias pr ON pr.id = lo.provincia_id
             JOIN paises pa ON pa.id = pr.pais_id
             WHERE lo.id = :id AND lo.activo = 1 AND pr.activo = 1 AND pa.activo = 1',
            ['id' => $id]
        );
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return $this->db->select(
            "SELECT id FROM localidades WHERE activo = 1 AND id IN ({$placeholders})",
            array_values($ids)
        );
    }

    public function propiedadesIds(int $id): array
    {
        $rows = $this->db->select(
            'SELECT id FROM propiedades WHERE localidad_id = :id ORDER BY id',
            ['id' => $id]
        );

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }
}
