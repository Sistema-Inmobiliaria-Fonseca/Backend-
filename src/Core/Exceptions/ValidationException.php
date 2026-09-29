<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class ValidationException extends HttpException
{
    public function __construct(string $message = 'Los datos enviados no son válidos.', array $details = [], int $statusCode = 422)
    {
        parent::__construct($statusCode, $message, $details);
    }

    public static function badRequest(string $message = 'Solicitud incorrecta.', array $details = []): self
    {
        return new self($message, $details, 400);
    }
}
