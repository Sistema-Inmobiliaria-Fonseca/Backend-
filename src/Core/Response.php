<?php

declare(strict_types=1);

namespace App\Core;

use JsonException;

final class Response
{
    private array $headers = [];

    public function __construct(
        private readonly string $body = '',
        private readonly int $status = 200,
        array $headers = []
    ) {
        foreach ($headers as $name => $value) {
            $this->headers[(string) $name] = (string) $value;
        }
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        try {
            $body = json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            $status = 500;
            $body = (string) json_encode([
                'success' => false,
                'error' => [
                    'code' => 500,
                    'message' => 'No se pudo serializar la respuesta: ' . $exception->getMessage(),
                ],
            ], JSON_UNESCAPED_UNICODE);
        }

        return new self($body, $status, array_merge(['Content-Type' => 'application/json; charset=UTF-8'], $headers));
    }

    public static function text(string $body, int $status = 200, array $headers = []): self
    {
        return new self($body, $status, array_merge(['Content-Type' => 'text/plain; charset=UTF-8'], $headers));
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }

    public function withHeaders(array $headers): self
    {
        $clone = $this;

        foreach ($headers as $name => $value) {
            $clone = $clone->withHeader((string) $name, (string) $value);
        }

        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        if ($this->status !== 204) {
            echo $this->body;
        }
    }
}
