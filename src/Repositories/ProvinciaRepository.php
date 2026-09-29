<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Catálogo geográfico: las provincias se cargan por seed y solo se consultan.
 */
final class ProvinciaRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(?int $paisId = null): array
    {
        $sql = 'SELECT pr.id, pr.pais_id, pr.nombre, pr.codigo, pr.activo,
                       pa.nombre AS pais_nombre, pa.codigo_iso AS pais_codigo_iso,
                       (SELECT COUNT(*) FROM localidades lo WHERE lo.provincia_id = pr.id AND lo.activo = 1) AS localidades_count
                FROM provincias pr
                JOIN paises pa ON pa.id = pr.pais_id
                WHERE pr.activo = 1 AND pa.activo = 1';

        $params = [];

        if ($paisId !== null) {
            $sql .= ' AND pr.pais_id = :pais_id';
            $params['pais_id'] = $paisId;
        }

        return $this->db->select($sql . ' ORDER BY pa.nombre ASC, pr.nombre ASC', $params);
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT pr.id, pr.pais_id, pr.nombre, pr.codigo, pr.activo,
                    pa.nombre AS pais_nombre, pa.codigo_iso AS pais_codigo_iso,
                    (SELECT COUNT(*) FROM localidades lo WHERE lo.provincia_id = pr.id AND lo.activo = 1) AS localidades_count
             FROM provincias pr
             JOIN paises pa ON pa.id = pr.pais_id
             WHERE pr.id = :id AND pr.activo = 1 AND pa.activo = 1',
            ['id' => $id]
        );
    }

    public function localidades(int $id): array
    {
        return $this->db->select(
            'SELECT id, nombre
             FROM localidades
             WHERE provincia_id = :id AND activo = 1
             ORDER BY nombre ASC',
            ['id' => $id]
        );
    }
}
