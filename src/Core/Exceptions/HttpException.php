<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;
use Throwable;

class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode = 500,
        string $message = '',
        private readonly array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message !== '' ? $message : self::defaultMessage($statusCode), $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function defaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Solicitud incorrecta.',
            401 => 'No autenticado.',
            403 => 'Acceso denegado.',
            404 => 'Recurso no encontrado.',
            405 => 'Método no permitido.',
            409 => 'Conflicto con el estado actual del recurso.',
            413 => 'La carga utile es demasiado grande.',
            415 => 'Tipo de contenido no soportado.',
            422 => 'Los datos enviados no son válidos.',
            429 => 'Demasiadas solicitudes.',
            500 => 'Error interno del servidor.',
            503 => 'Servicio no disponible.',
            default => 'Error inesperado.',
        };
    }
}
