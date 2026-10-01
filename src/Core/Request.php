<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

final class Request
{
    private ?array $json = null;

    private array $routeParams = [];

    private ?array $user = null;

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $headers = [],
        private readonly array $server = []
    ) {
    }

    public static function capture(): self
    {
        $server = $_SERVER;
        $method = strtoupper((string) ($server['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($server['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));
        $contentType = (string) ($server['CONTENT_TYPE'] ?? $server['HTTP_CONTENT_TYPE'] ?? '');

        return new self(
            $method,
            $path === '' ? '/' : $path,
            $_GET,
            self::parseBody($contentType),
            self::captureHeaders($server),
            $server
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function url(): string
    {
        $scheme = ($this->server['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
        $host = (string) ($this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? 'localhost');

        return $scheme . '://' . $host . ($this->server['REQUEST_URI'] ?? $this->path);
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->query[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return array_merge($this->query, $this->body);
        }

        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body) || array_key_exists($key, $this->query);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }

        if ($name === 'Content-Type' && isset($this->headers['CONTENT_TYPE'])) {
            return $this->headers['CONTENT_TYPE'];
        }

        if ($name === 'Content-Length' && isset($this->headers['CONTENT_LENGTH'])) {
            return $this->headers['CONTENT_LENGTH'];
        }

        return $default;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }

    public function routeParams(): array
    {
        return $this->routeParams;
    }

    public function routeParam(string $name, mixed $default = null): mixed
    {
        return $this->routeParams[$name] ?? $default;
    }

    public function withRouteParams(array $params): self
    {
        $clone = clone $this;
        $clone->routeParams = $params;

        return $clone;
    }

    /**
     * Usuario inyectado por AuthenticateMiddleware.
     */
    public function user(): ?array
    {
        return $this->user;
    }

    public function withUser(array $user): self
    {
        $clone = clone $this;
        $clone->user = $user;

        return $clone;
    }

    private static function parseBody(string $contentType): array
    {
        if (!str_contains(strtolower($contentType), 'application/json')) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw Exceptions\ValidationException::badRequest('El cuerpo de la petición no es JSON válido.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if (!is_array($decoded)) {
            return ['__body' => $decoded];
        }

        return $decoded;
    }

    private static function captureHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (str_starts_with((string) $key, 'HTTP_')) {
                $headers[$key] = (string) $value;
            }
        }

        foreach (['CONTENT_TYPE', 'CONTENT_LENGTH'] as $key) {
            if (isset($server[$key])) {
                $headers[$key] = (string) $server[$key];
            }
        }

        return $headers;
    }
}
