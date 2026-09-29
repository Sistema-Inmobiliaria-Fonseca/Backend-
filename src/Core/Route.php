<?php

declare(strict_types=1);

namespace App\Core;

final class Route
{
    private array $parameters = [];

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly string $pattern,
        private readonly mixed $handler,
        private readonly array $middleware = []
    ) {
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function handler(): mixed
    {
        return $this->handler;
    }

    public function middleware(): array
    {
        return $this->middleware;
    }

    public function matchesPath(string $path): bool
    {
        if (!preg_match($this->pattern, $path, $matches)) {
            return false;
        }

        $this->parameters = array_filter(
            $matches,
            static fn (string|int $key): bool => is_string($key),
            ARRAY_FILTER_USE_KEY
        );

        return true;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }
}
