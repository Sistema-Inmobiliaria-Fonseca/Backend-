<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use JsonException;
use RuntimeException;

/**
 * Tokens de sesión firmados con HMAC-SHA256: `payload.signature` en base64url.
 *
 * Son sin estado (no hay tabla de tokens), así que el logout del panel se resuelve
 * en el frontend descartando la sesión local. El payload solo lleva el id del
 * usuario y las marcas de tiempo.
 */
final class TokenService
{
    public function __construct(private readonly Config $config)
    {
    }

    public function issue(int $usuarioId): string
    {
        $ahora = time();
        $payload = [
            'uid' => $usuarioId,
            'iat' => $ahora,
            'exp' => $ahora + $this->ttl(),
        ];

        $cuerpo = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        return $cuerpo . '.' . $this->base64UrlEncode($this->sign($cuerpo));
    }

    /**
     * Devuelve el id del usuario del token, o null si viene mal formado, expiró
     * o la firma no coincide.
     */
    public function verify(string $token): ?int
    {
        $partes = explode('.', $token);

        if (count($partes) !== 2) {
            return null;
        }

        [$cuerpo, $firma] = $partes;

        $esperada = $this->base64UrlEncode($this->sign($cuerpo));

        if (!hash_equals($esperada, $firma)) {
            return null;
        }

        try {
            $payload = json_decode($this->base64UrlDecode($cuerpo), true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($payload) || !isset($payload['uid'], $payload['exp'])) {
            return null;
        }

        if ((int) $payload['exp'] < time()) {
            return null;
        }

        return (int) $payload['uid'];
    }

    public function ttl(): int
    {
        return max(300, (int) $this->config->get('app.auth.token_ttl', 43200));
    }

    private function sign(string $cuerpo): string
    {
        return hash_hmac('sha256', $cuerpo, $this->secret(), true);
    }

    private function secret(): string
    {
        $secret = trim((string) $this->config->get('app.auth.token_secret', ''));

        if ($secret === '') {
            throw new RuntimeException(
                'Falta definir AUTH_TOKEN_SECRET en el .env: sin ese secreto no se pueden firmar los tokens.'
            );
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $restante = strlen($value) % 4;

        if ($restante !== 0) {
            $value .= str_repeat('=', 4 - $restante);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? '' : $decoded;
    }
}