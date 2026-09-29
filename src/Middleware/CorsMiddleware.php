<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

final class CorsMiddleware implements Middleware
{
    public function __construct(
        private readonly array $allowedOrigins = ['*'],
        private readonly string $allowedMethods = 'GET,POST,PUT,PATCH,DELETE,OPTIONS',
        private readonly string $allowedHeaders = 'Content-Type,Authorization,X-Requested-With',
        private readonly int $maxAge = 86400,
        private readonly bool $supportsCredentials = false
    ) {
    }

    public function handle(Request $request, callable $next): Response
    {
        $response = $request->isMethod('OPTIONS') ? Response::noContent() : $next($request);
        $origin = $this->resolveOrigin($request->header('Origin'));

        if ($origin === null) {
            return $response;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowedMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->allowedHeaders)
            ->withHeader('Access-Control-Max-Age', (string) $this->maxAge);

        if ($origin !== '*') {
            $response = $response->withHeader('Vary', 'Origin');
        }

        if ($this->supportsCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function resolveOrigin(?string $origin): ?string
    {
        if (in_array('*', $this->allowedOrigins, true)) {
            return $origin ?? '*';
        }

        if ($origin === null) {
            return null;
        }

        return in_array($origin, $this->allowedOrigins, true) ? $origin : null;
    }
}
