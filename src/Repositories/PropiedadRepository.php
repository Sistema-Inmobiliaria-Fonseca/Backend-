<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PropiedadRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    private const SELECT = 'SELECT p.id, p.nombre, p.localidad_id, p.metros_cuadrados, p.valor,
                                   p.cantidad_habitaciones, p.cantidad_ambientes, p.descripcion,
                                   p.apto_credito, p.estado, p.created_at, p.updated_at,
                                   lo.nombre AS localidad_nombre,
                                   pr.id AS provincia_id, pr.nombre AS provincia_nombre,
                                   pa.id AS pais_id, pa.nombre AS pais_nombre, pa.codigo_iso AS pais_codigo_iso
                            FROM propiedades p
                            LEFT JOIN localidades lo ON lo.id = p.localidad_id
                            LEFT JOIN provincias pr ON pr.id = lo.provincia_id
                            LEFT JOIN paises pa ON pa.id = pr.pais_id';

    public function all(): array
    {
        return $this->db->select(self::SELECT . ' ORDER BY p.id DESC');
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(self::SELECT . ' WHERE p.id = :id', ['id' => $id]);
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO propiedades
                 (nombre, localidad_id, metros_cuadrados, valor, cantidad_habitaciones, cantidad_ambientes,
                  descripcion, apto_credito, estado)
             VALUES
                 (:nombre, :localidad_id, :metros_cuadrados, :valor, :cantidad_habitaciones, :cantidad_ambientes,
                  :descripcion, :apto_credito, :estado)',
            $this->params($data)
        );
    }

    public function update(int $id, array $data): int
    {
        $params = $this->params($data);
        $params['id'] = $id;

        return $this->db->affectedRows(
            'UPDATE propiedades
             SET nombre = :nombre,
                 localidad_id = :localidad_id,
                 metros_cuadrados = :metros_cuadrados,
                 valor = :valor,
                 cantidad_habitaciones = :cantidad_habitaciones,
                 cantidad_ambientes = :cantidad_ambientes,
                 descripcion = :descripcion,
                 apto_credito = :apto_credito,
                 estado = :estado
             WHERE id = :id',
            $params
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->statement('DELETE FROM propiedades WHERE id = :id', ['id' => $id]);
    }

    private function params(array $data): array
    {
        return [
            'nombre' => $data['nombre'],
            'localidad_id' => $data['localidad_id'] ?? null,
            'metros_cuadrados' => $data['metros_cuadrados'] ?? null,
            'valor' => $data['valor'] ?? null,
            'cantidad_habitaciones' => (int) ($data['cantidad_habitaciones'] ?? 0),
            'cantidad_ambientes' => (int) ($data['cantidad_ambientes'] ?? 0),
            'descripcion' => $data['descripcion'] ?? null,
            'apto_credito' => (int) (bool) ($data['apto_credito'] ?? false),
            'estado' => $data['estado'] ?? 'disponible',
        ];
    }
}
