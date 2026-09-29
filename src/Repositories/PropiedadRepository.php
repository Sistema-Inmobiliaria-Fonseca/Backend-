<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PropiedadRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        return $this->db->select(
            'SELECT id, nombre, metros_cuadrados, valor, cantidad_habitaciones, cantidad_ambientes,
                    descripcion, apto_credito, estado, created_at, updated_at
             FROM propiedades
             ORDER BY id DESC'
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT id, nombre, metros_cuadrados, valor, cantidad_habitaciones, cantidad_ambientes,
                    descripcion, apto_credito, estado, created_at, updated_at
             FROM propiedades
             WHERE id = :id',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO propiedades
                 (nombre, metros_cuadrados, valor, cantidad_habitaciones, cantidad_ambientes,
                  descripcion, apto_credito, estado)
             VALUES
                 (:nombre, :metros_cuadrados, :valor, :cantidad_habitaciones, :cantidad_ambientes,
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
