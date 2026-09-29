<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Catálogo geográfico: los países se cargan por seed y solo se consultan.
 */
final class PaisRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select(
            'SELECT pa.id, pa.nombre, pa.codigo_iso, pa.activo,
                    COUNT(DISTINCT pr.id) AS provincias_count,
                    COUNT(lo.id) AS localidades_count
             FROM paises pa
             LEFT JOIN provincias pr ON pr.pais_id = pa.id AND pr.activo = 1
             LEFT JOIN localidades lo ON lo.provincia_id = pr.id AND lo.activo = 1
             WHERE pa.activo = 1
             GROUP BY pa.id, pa.nombre, pa.codigo_iso, pa.activo
             ORDER BY pa.nombre ASC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT pa.id, pa.nombre, pa.codigo_iso, pa.activo,
                    (SELECT COUNT(*) FROM provincias pr WHERE pr.pais_id = pa.id AND pr.activo = 1) AS provincias_count,
                    (SELECT COUNT(*) FROM localidades lo
                       JOIN provincias pr ON pr.id = lo.provincia_id
                      WHERE pr.pais_id = pa.id AND lo.activo = 1) AS localidades_count
             FROM paises pa
             WHERE pa.id = :id AND pa.activo = 1',
            ['id' => $id]
        );
    }
}
