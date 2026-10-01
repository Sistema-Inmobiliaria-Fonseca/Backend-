<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Exceptions\UnauthorizedHttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Exige un token Bearer válido y adjunta el usuario autenticado a la petición,
 * disponible para los controladores con Request::user().
 */
final class AuthenticateMiddleware implements Middleware
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $this->bearerToken($request);

        if ($token === null) {
            throw new UnauthorizedHttpException('Falta el encabezado Authorization con el token.');
        }

        $usuario = $this->auth->usuarioDesdeToken($token);

        if ($usuario === null) {
            throw new UnauthorizedHttpException('El token es inválido o expiró.');
        }

        return $next($request->withUser($usuario));
    }

    private function bearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if ($header === null) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) !== 1) {
            return null;
        }

        $token = trim($matches[1]);

        return $token === '' ? null : $token;
    }
}