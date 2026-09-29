<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class MethodNotAllowedHttpException extends HttpException
{
    public function __construct(private readonly array $allowedMethods = [], string $message = 'Método no permitido.')
    {
        parent::__construct(405, $message);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
