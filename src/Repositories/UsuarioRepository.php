<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UsuarioRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->selectOne(
            'SELECT id, nombre, email, password, rol, activo, ultimo_acceso_at, created_at, updated_at
             FROM usuarios
             WHERE email = :email',
            ['email' => $email]
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->selectOne(
            'SELECT id, nombre, email, password, rol, activo, ultimo_acceso_at, created_at, updated_at
             FROM usuarios
             WHERE id = :id',
            ['id' => $id]
        );
    }

    public function touchAcceso(int $id): void
    {
        $this->db->statement(
            'UPDATE usuarios SET ultimo_acceso_at = CURRENT_TIMESTAMP WHERE id = :id',
            ['id' => $id]
        );
    }
}