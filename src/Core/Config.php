<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Config
{
    private array $items = [];

    public function __construct(string $path)
    {
        $this->loadDirectory($path);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    public function all(): array
    {
        return $this->items;
    }

    private function loadDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new RuntimeException("El directorio de configuración no existe: {$path}");
        }

        $files = glob(rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            $config = require $file;

            if (!is_array($config)) {
                throw new RuntimeException("El archivo de configuración debe devolver un array: {$file}");
            }

            $this->items[$name] = $config;
        }
    }
}
