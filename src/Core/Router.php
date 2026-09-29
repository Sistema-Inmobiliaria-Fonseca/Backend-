<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\MethodNotAllowedHttpException;
use App\Core\Exceptions\NotFoundHttpException;

final class Router
{
    private const PARAMETER_PATTERN = '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#';

    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $this->routes[] = new Route(
            strtoupper($method),
            $path,
            $this->compile($path),
            $handler,
            $middleware
        );
    }

    public function match(Request $request): Route
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (!$route->matchesPath($request->path())) {
                continue;
            }

            if ($route->method() !== $request->method()) {
                $allowedMethods[] = $route->method();
                continue;
            }

            return $route;
        }

        if ($allowedMethods !== []) {
            throw new MethodNotAllowedHttpException(array_values(array_unique($allowedMethods)));
        }

        throw new NotFoundHttpException(
            "No existe el endpoint {$request->method()} {$request->path()}."
        );
    }

    public function routes(): array
    {
        return $this->routes;
    }

    private function compile(string $path): string
    {
        $pattern = preg_replace(self::PARAMETER_PATTERN, '(?P<$1>[^/]+)', $path) ?? $path;

        return '#^' . $pattern . '$#';
    }
}
