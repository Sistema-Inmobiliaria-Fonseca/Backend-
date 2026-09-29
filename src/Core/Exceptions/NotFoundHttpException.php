<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

final class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Recurso no encontrado.', array $details = [])
    {
        parent::__construct(404, $message, $details);
    }
}
