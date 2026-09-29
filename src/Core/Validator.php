<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ValidationException;

final class Validator
{
    public static function validate(array $data, array $rules, array $labels = []): array
    {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $ruleList) {
            $fieldRules = is_array($ruleList) ? $ruleList : explode('|', $ruleList);
            $label = $labels[$field] ?? $field;
            $isPresent = array_key_exists($field, $data);
            $value = $isPresent ? $data[$field] : null;

            if (!$isPresent || $value === null || $value === '') {
                if (in_array('required', $fieldRules, true)) {
                    $errors[$field] = "El campo {$label} es obligatorio.";
                    continue;
                }

                if (in_array('nullable', $fieldRules, true) || !in_array('required', $fieldRules, true)) {
                    continue;
                }
            }

            $result = self::applyRules($fieldRules, $value, $label);

            if ($result['error'] !== null) {
                $errors[$field] = $result['error'];
                continue;
            }

            if ($result['value'] !== null) {
                $validated[$field] = $result['value'];
            }
        }

        if ($errors !== []) {
            throw new ValidationException(
                'Los datos enviados no son válidos.',
                $errors
            );
        }

        return $validated;
    }

    public static function toBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) && ($value === 0 || $value === 1)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'si', 'sí' => true,
                '0', 'false', 'no' => false,
                default => null,
            };
        }

        return null;
    }

    private static function applyRules(array $rules, mixed $value, string $label): array
    {
        foreach ($rules as $rule) {
            [$name, $parameter] = array_pad(explode(':', (string) $rule, 2), 2, null);

            $outcome = match ($name) {
                'nullable' => ['value' => $value, 'error' => null],
                'string' => self::ruleString($value, $label),
                'integer' => self::ruleInteger($value, $label),
                'numeric' => self::ruleNumeric($value, $label),
                'boolean' => self::ruleBoolean($value, $label),
                'array' => self::ruleArray($value, $label),
                'in' => self::ruleIn($value, $label, (string) $parameter),
                'min' => self::ruleMin($value, $label, (float) $parameter),
                'max' => self::ruleMax($value, $label, $parameter),
                default => ['value' => $value, 'error' => null],
            };

            if ($outcome['error'] !== null) {
                return ['value' => null, 'error' => $outcome['error']];
            }

            $value = $outcome['value'];
        }

        return ['value' => $value, 'error' => null];
    }

    private static function ruleString(mixed $value, string $label): array
    {
        if (is_string($value)) {
            return ['value' => trim($value), 'error' => null];
        }

        if (is_int($value) || is_float($value)) {
            return ['value' => (string) $value, 'error' => null];
        }

        return ['value' => null, 'error' => "El campo {$label} debe ser texto."];
    }

    private static function ruleInteger(mixed $value, string $label): array
    {
        if (is_bool($value)) {
            return ['value' => null, 'error' => "El campo {$label} debe ser un número entero."];
        }

        if (is_int($value)) {
            return ['value' => $value, 'error' => null];
        }

        if (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1) {
            return ['value' => (int) trim($value), 'error' => null];
        }

        if (is_float($value) && floor($value) === $value) {
            return ['value' => (int) $value, 'error' => null];
        }

        return ['value' => null, 'error' => "El campo {$label} debe ser un número entero."];
    }

    private static function ruleNumeric(mixed $value, string $label): array
    {
        if (is_bool($value) || (!is_int($value) && !is_float($value) && !is_string($value))) {
            return ['value' => null, 'error' => "El campo {$label} debe ser numérico."];
        }

        if (is_numeric(trim((string) $value))) {
            return ['value' => (float) trim((string) $value), 'error' => null];
        }

        return ['value' => null, 'error' => "El campo {$label} debe ser numérico."];
    }

    private static function ruleBoolean(mixed $value, string $label): array
    {
        $boolean = self::toBoolean($value);

        if ($boolean === null) {
            return ['value' => null, 'error' => "El campo {$label} debe ser verdadero o falso."];
        }

        return ['value' => $boolean, 'error' => null];
    }

    private static function ruleArray(mixed $value, string $label): array
    {
        if (is_array($value)) {
            return ['value' => array_values($value), 'error' => null];
        }

        return ['value' => null, 'error' => "El campo {$label} debe ser una lista."];
    }

    private static function ruleIn(mixed $value, string $label, string $parameter): array
    {
        $allowed = array_filter(array_map('trim', explode(',', $parameter)), static fn (string $item): bool => $item !== '');

        if (in_array((string) $value, $allowed, true)) {
            return ['value' => (string) $value, 'error' => null];
        }

        return [
            'value' => null,
            'error' => "El campo {$label} debe ser uno de estos valores: " . implode(', ', $allowed) . '.',
        ];
    }

    private static function ruleMin(mixed $value, string $label, float $minimum): array
    {
        if (!is_numeric($value)) {
            return ['value' => null, 'error' => "El campo {$label} debe ser numérico."];
        }

        if ((float) $value < $minimum) {
            return ['value' => null, 'error' => "El campo {$label} no puede ser menor que {$minimum}."];
        }

        return ['value' => $value, 'error' => null];
    }

    private static function ruleMax(mixed $value, string $label, ?string $parameter): array
    {
        if ($parameter === null) {
            return ['value' => $value, 'error' => null];
        }

        if (is_string($value)) {
            if (mb_strlen($value) > (int) $parameter) {
                return ['value' => null, 'error' => "El campo {$label} no puede superar los {$parameter} caracteres."];
            }

            return ['value' => $value, 'error' => null];
        }

        if (is_numeric($value) && (float) $value > (float) $parameter) {
            return ['value' => null, 'error' => "El campo {$label} no puede ser mayor que {$parameter}."];
        }

        return ['value' => $value, 'error' => null];
    }
}
