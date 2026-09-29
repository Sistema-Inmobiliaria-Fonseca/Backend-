<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    private array $bindings = [];

    private array $shared = [];

    private array $instances = [];

    public function bind(string $id, callable $factory, bool $shared = false): void
    {
        $this->bindings[$id] = $factory;
        $this->shared[$id] = $shared;
        unset($this->instances[$id]);
    }

    public function instance(string $id, mixed $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->bindings[$id], $this->shared[$id]);
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]) || array_key_exists($id, $this->instances);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (isset($this->bindings[$id])) {
            $factory = $this->bindings[$id];
            $object = $factory($this);

            if ($this->shared[$id] ?? false) {
                $this->instances[$id] = $object;
            }

            return $object;
        }

        if (!class_exists($id)) {
            throw new RuntimeException("No se pudo resolver el servicio [{$id}]: no está registrado en el contenedor.");
        }

        $object = $this->build($id);
        $this->instances[$id] = $object;

        return $object;
    }

    private function build(string $class): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflection->newInstance();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                "No se puede inyectar el parámetro \${$parameter->getName()} de [{$class}]: "
                . 'escribí el tipo o dale un valor por defecto.'
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
