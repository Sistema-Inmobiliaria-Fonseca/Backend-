<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PropiedadImagenRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT id, propiedad_id, nombre_archivo, nombre_original, mime_type, tamano, orden, created_at
             FROM propiedad_imagenes
             WHERE id = :id',
            ['id' => $id]
        );
    }

    public function findDePropiedad(int $propiedadId, int $imagenId): ?array
    {
        return $this->db->selectOne(
            'SELECT id, propiedad_id, nombre_archivo, nombre_original, mime_type, tamano, orden, created_at
             FROM propiedad_imagenes
             WHERE id = :id AND propiedad_id = :propiedad_id',
            ['id' => $imagenId, 'propiedad_id' => $propiedadId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dePropiedad(int $propiedadId): array
    {
        return $this->db->select(
            'SELECT id, propiedad_id, nombre_archivo, nombre_original, mime_type, tamano, orden, created_at
             FROM propiedad_imagenes
             WHERE propiedad_id = :propiedad_id
             ORDER BY orden ASC, id ASC',
            ['propiedad_id' => $propiedadId]
        );
    }

    /**
     * Imagenes de varias propiedades en una sola consulta, para no hacer una
     * consulta por propiedad en el listado.
     *
     * @param  array<int, int>  $propiedadIds
     * @return array<int, array<int, array<string, mixed>>>  agrupadas por propiedad_id
     */
    public function dePropiedades(array $propiedadIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $propiedadIds)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));

        $rows = $this->db->select(
            "SELECT id, propiedad_id, nombre_archivo, nombre_original, mime_type, tamano, orden, created_at
             FROM propiedad_imagenes
             WHERE propiedad_id IN ({$placeholders})
             ORDER BY propiedad_id ASC, orden ASC, id ASC",
            $ids
        );

        $porPropiedad = [];

        foreach ($rows as $row) {
            $porPropiedad[(int) $row['propiedad_id']][] = $row;
        }

        return $porPropiedad;
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO propiedad_imagenes (propiedad_id, nombre_archivo, nombre_original, mime_type, tamano, orden)
             VALUES (:propiedad_id, :nombre_archivo, :nombre_original, :mime_type, :tamano, :orden)',
            [
                'propiedad_id' => (int) $data['propiedad_id'],
                'nombre_archivo' => (string) $data['nombre_archivo'],
                'nombre_original' => (string) $data['nombre_original'],
                'mime_type' => (string) $data['mime_type'],
                'tamano' => (int) $data['tamano'],
                'orden' => (int) $data['orden'],
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->db->statement('DELETE FROM propiedad_imagenes WHERE id = :id', ['id' => $id]);
    }

    public function deleteDePropiedad(int $propiedadId): bool
    {
        return $this->db->statement(
            'DELETE FROM propiedad_imagenes WHERE propiedad_id = :propiedad_id',
            ['propiedad_id' => $propiedadId]
        );
    }

    public function existeEnPropiedad(int $propiedadId, string $nombreArchivo): bool
    {
        return $this->db->scalar(
            'SELECT 1 FROM propiedad_imagenes WHERE propiedad_id = :propiedad_id AND nombre_archivo = :nombre LIMIT 1',
            ['propiedad_id' => $propiedadId, 'nombre' => $nombreArchivo]
        ) !== null;
    }

    public function siguienteOrden(int $propiedadId): int
    {
        return (int) $this->db->scalar(
            'SELECT COALESCE(MAX(orden), 0) + 1 FROM propiedad_imagenes WHERE propiedad_id = :propiedad_id',
            ['propiedad_id' => $propiedadId]
        );
    }

    public function contar(int $propiedadId): int
    {
        return (int) $this->db->scalar(
            'SELECT COUNT(*) FROM propiedad_imagenes WHERE propiedad_id = :propiedad_id',
            ['propiedad_id' => $propiedadId]
        );
    }

    /**
     * Orden actual de un conjunto de ids en una sola consulta.
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>  [id => orden]
     */
    public function ordenesPorIds(array $ids): array
    {
        $lista = array_values(array_unique(array_map('intval', $ids)));

        if ($lista === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($lista), '?'));

        $rows = $this->db->select(
            "SELECT id, orden FROM propiedad_imagenes WHERE id IN ({$placeholders})",
            $lista
        );

        $ordenes = [];

        foreach ($rows as $row) {
            $ordenes[(int) $row['id']] = (int) $row['orden'];
        }

        return $ordenes;
    }

    /**
     * Mueve todos los ordenes de la propiedad a un rango libre. Deja disponibles
     * los valores 1..n para escribir el orden final sin chocar contra el indice
     * unico (propiedad_id, orden). MySQL y MariaDB validan el indice en cada
     * sentencia, asi que el reordenamiento se hace en dos pasos dentro de la misma
     * transaccion: primero se reservan los valores, despues se escribe el orden final.
     */
    public function reservarOrdenes(int $propiedadId, int $offset): int
    {
        return $this->db->affectedRows(
            'UPDATE propiedad_imagenes SET orden = orden + :offset WHERE propiedad_id = :propiedad_id',
            ['offset' => $offset, 'propiedad_id' => $propiedadId]
        );
    }

    public function maximoOrden(int $propiedadId): int
    {
        return (int) $this->db->scalar(
            'SELECT COALESCE(MAX(orden), 0) FROM propiedad_imagenes WHERE propiedad_id = :propiedad_id',
            ['propiedad_id' => $propiedadId]
        );
    }

    public function actualizarOrden(int $imagenId, int $orden): int
    {
        return $this->db->affectedRows(
            'UPDATE propiedad_imagenes SET orden = :orden WHERE id = :id',
            ['orden' => $orden, 'id' => $imagenId]
        );
    }

    public function actualizarOrdenDePropiedad(int $propiedadId, int $imagenId, int $orden): int
    {
        return $this->db->affectedRows(
            'UPDATE propiedad_imagenes SET orden = :orden WHERE id = :id AND propiedad_id = :propiedad_id',
            ['orden' => $orden, 'id' => $imagenId, 'propiedad_id' => $propiedadId]
        );
    }

    /**
     * Renumera las imagenes de la propiedad a 1..n segun el orden actual.
     * Se usa al borrar para que no queden huecos.
     */
    public function compactarOrdenes(int $propiedadId): void
    {
        foreach ($this->dePropiedad($propiedadId) as $posicion => $fila) {
            $this->actualizarOrdenDePropiedad($propiedadId, (int) $fila['id'], $posicion + 1);
        }
    }
}