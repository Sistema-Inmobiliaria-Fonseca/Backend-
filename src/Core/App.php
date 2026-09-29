<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;

final class App
{
    private readonly Router $router;

    private array $middleware = [];

    public function __construct(private readonly Container $container)
    {
        $this->router = new Router();
    }

    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->router->get($path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->router->post($path, $handler, $middleware);
    }

    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->router->put($path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->router->patch($path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->router->delete($path, $handler, $middleware);
    }

    public function use(Middleware $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function handle(Request $request): Response
    {
        $pipeline = $this->pipeline($this->middleware, fn (Request $request): Response => $this->dispatch($request));

        return $pipeline($request);
    }

    public function run(?Request $request = null): void
    {
        try {
            $request ??= Request::capture();
        } catch (HttpException $exception) {
            $this->failEarly($exception)->send();

            return;
        }

        $this->handle($request)->send();
    }

    private function failEarly(HttpException $exception): Response
    {
        $error = [
            'code' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
        ];

        if ($exception->getDetails() !== []) {
            $error['details'] = $exception->getDetails();
        }

        $response = Response::json(['success' => false, 'error' => $error], $exception->getStatusCode());
        $request = new Request('UNKNOWN', '/', [], [], [], $_SERVER);

        return ($this->pipeline($this->middleware, fn (Request $request): Response => $response))($request);
    }

    private function dispatch(Request $request): Response
    {
        $route = $this->router->match($request);
        $request = $request->withRouteParams($route->parameters());

        $destination = fn (Request $request): Response => $this->callAction($route->handler(), $request);

        return ($this->pipeline($route->middleware(), $destination))($request);
    }

    private function callAction(mixed $handler, Request $request): Response
    {
        $parameters = array_map(
            static fn (string $value): mixed => preg_match('/^-?\d+$/', $value) === 1 ? (int) $value : $value,
            array_values($request->routeParams())
        );

        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $this->container->get($class);
            $result = $controller->{$method}($request, ...$parameters);
        } else {
            $result = $handler($request, ...$parameters);
        }

        return $this->toResponse($result);
    }

    private function toResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result === null) {
            return Response::noContent();
        }

        if (is_array($result) && array_key_exists('success', $result)) {
            return Response::json($result);
        }

        return Response::json(['success' => true, 'data' => $result]);
    }

    private function pipeline(array $middleware, callable $destination): callable
    {
        $pipeline = $destination;

        foreach (array_reverse($middleware) as $entry) {
            $next = $pipeline;

            $pipeline = function (Request $request) use ($entry, $next): Response {
                $instance = $entry instanceof Middleware ? $entry : $this->container->get($entry);

                return $instance->handle($request, $next);
            };
        }

        return $pipeline;
    }
}
