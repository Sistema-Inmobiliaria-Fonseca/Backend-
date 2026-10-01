<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\UnauthorizedHttpException;
use App\Core\Validator;
use App\Repositories\UsuarioRepository;

final class AuthService
{
    private const RULES = [
        'email' => 'required|string|max:190',
        'password' => 'required|string|max:255',
        'remember' => 'nullable|boolean',
    ];

    private const LABELS = [
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'remember' => 'recordarme',
    ];

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly TokenService $tokens
    ) {
    }

    public function iniciarSesion(array $datos): array
    {
        $validados = Validator::validate($datos, self::RULES, self::LABELS);
        $email = mb_strtolower($validados['email']);

        $usuario = $this->usuarios->findByEmail($email);

        if ($usuario === null || !$this->coincide($validados['password'], $usuario['password'])) {
            throw new UnauthorizedHttpException('Las credenciales son incorrectas.');
        }

        if (!(bool) $usuario['activo']) {
            throw new UnauthorizedHttpException('El usuario está desactivado.');
        }

        $this->usuarios->touchAcceso((int) $usuario['id']);

        return [
            'token' => $this->tokens->issue((int) $usuario['id']),
            'expires_in' => $this->tokens->ttl(),
            'user' => $this->formatear($usuario),
        ];
    }

    /**
     * Resuelve el usuario del token y lo devuelve formateado, o null si el token
     * es inválido, expiró o el usuario ya no existe o está desactivado.
     */
    public function usuarioDesdeToken(string $token): ?array
    {
        $usuarioId = $this->tokens->verify($token);

        if ($usuarioId === null) {
            return null;
        }

        $usuario = $this->usuarios->find($usuarioId);

        if ($usuario === null || !(bool) $usuario['activo']) {
            return null;
        }

        return $this->formatear($usuario);
    }

    private function coincide(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function formatear(array $usuario): array
    {
        return [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
        ];
    }
}