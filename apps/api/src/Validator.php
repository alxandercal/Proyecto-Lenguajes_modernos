<?php
declare(strict_types=1);

namespace ReFood;

// Validaciones de entrada. Cada error dice qué campo falló, en español.
final class Validator
{
    private const NAMES = [
        'name' => 'nombre',
        'organizationName' => 'organización',
        'zone' => 'zona',
        'role' => 'tipo de cuenta',
        'title' => 'título',
        'category' => 'categoría',
        'quantity' => 'cantidad',
        'unit' => 'unidad',
        'expiresAt' => 'vencimiento',
        'pickupAddress' => 'punto de recogida',
        'notes' => 'notas',
        'receivedBy' => 'quién recibió',
        'note' => 'nota',
        'evidenceUrl' => 'enlace de evidencia',
        'donationId' => 'donación',
        'action' => 'acción',
    ];

    private static function label(string $field): string
    {
        return self::NAMES[$field] ?? $field;
    }

    public static function text(array $data, string $field, int $min = 1, int $max = 180): string
    {
        $value = $data[$field] ?? null;
        if (!is_string($value)) {
            throw new ApiException('Falta el campo ' . self::label($field) . '.', 422);
        }
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        $length = mb_strlen($value);
        if ($length < $min || $length > $max) {
            throw new ApiException(
                'El campo ' . self::label($field) . " debe tener entre {$min} y {$max} caracteres.",
                422
            );
        }
        return $value;
    }

    // Texto que puede venir vacío. Devuelve '' si no viene.
    public static function optionalText(array $data, string $field, int $max = 300): string
    {
        if (!isset($data[$field]) || $data[$field] === '') {
            return '';
        }
        return self::text($data, $field, 0, $max);
    }

    public static function oneOf(array $data, string $field, array $allowed): string
    {
        $value = $data[$field] ?? null;
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new ApiException('El valor de ' . self::label($field) . ' no es válido.', 422);
        }
        return $value;
    }

    public static function number(array $data, string $field, float $min, float $max): float
    {
        $value = $data[$field] ?? null;
        if (is_string($value) && is_numeric($value)) {
            $value = (float) $value;
        }
        if (!is_int($value) && !is_float($value)) {
            throw new ApiException('El campo ' . self::label($field) . ' debe ser un número.', 422);
        }
        $value = (float) $value;
        if (!is_finite($value) || $value < $min || $value > $max) {
            throw new ApiException(
                'El campo ' . self::label($field) . " debe estar entre {$min} y {$max}.",
                422
            );
        }
        return round($value, 2);
    }

    // Fecha futura en formato ISO 8601. Se guarda siempre en UTC: 2026-11-20T18:00:00Z
    public static function futureDate(array $data, string $field, \DateTimeImmutable $now, int $maxDays = 30): string
    {
        $value = $data[$field] ?? null;
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value)) {
            throw new ApiException('La fecha de ' . self::label($field) . ' no es válida.', 422);
        }
        try {
            $date = new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ApiException('La fecha de ' . self::label($field) . ' no es válida.', 422);
        }
        if ($date <= $now) {
            throw new ApiException('La fecha de ' . self::label($field) . ' ya pasó.', 422);
        }
        if ($date > $now->modify("+{$maxDays} days")) {
            throw new ApiException("El vencimiento no puede ser a más de {$maxDays} días.", 422);
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    // Enlace opcional que debe empezar con https://
    public static function optionalUrl(array $data, string $field): string
    {
        $value = self::optionalText($data, $field, 500);
        if ($value === '') {
            return '';
        }
        if (!str_starts_with($value, 'https://') || filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new ApiException('El ' . self::label($field) . ' debe empezar con https://', 422);
        }
        return $value;
    }

    // Identificadores de documentos: letras, números, guion y guion bajo. Evita rutas como ../
    public static function documentId(string $id): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]{6,160}$/', $id)) {
            throw new ApiException('Identificador inválido.', 400);
        }
        return $id;
    }
}
