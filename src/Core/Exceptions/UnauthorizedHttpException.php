<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class UnauthorizedHttpException extends HttpException
{
    public function __construct(string $message = 'No autenticado.', array $details = [])
    {
        parent::__construct(401, $message, $details);
    }
}