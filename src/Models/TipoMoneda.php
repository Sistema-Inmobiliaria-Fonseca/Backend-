<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Monedas en las que se puede expresar el valor de una propiedad.
 * El nombre del caso es exactamente el valor persistido en propiedades.moneda
 * (columna VARCHAR(10) de la migration 011).
 */
enum TipoMoneda: string
{
    case ARS = 'ARS';
    case USD = 'USD';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_map(static fn (self $caso): string => $caso->value, self::cases());
    }
}
