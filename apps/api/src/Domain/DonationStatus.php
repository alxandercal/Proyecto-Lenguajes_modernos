<?php
declare(strict_types=1);

namespace ReFood\Domain;

use ReFood\ApiException;

// Regla de negocio central: qué cambios de estado se permiten y quién los hace.
final class DonationStatus
{
    public const PUBLISHED = 'PUBLISHED';
    public const RESERVED = 'RESERVED';
    public const DELIVERED = 'DELIVERED';
    public const CANCELLED = 'CANCELLED';

    // desde => [hacia => rol que puede hacerlo]
    private const TRANSITIONS = [
        self::PUBLISHED => [
            self::RESERVED => 'RECEIVER',
            self::CANCELLED => 'DONOR',
        ],
        self::RESERVED => [
            self::PUBLISHED => 'RECEIVER',
            self::DELIVERED => 'DONOR',
            self::CANCELLED => 'DONOR',
        ],
        self::DELIVERED => [],
        self::CANCELLED => [],
    ];

    public static function canMove(string $from, string $to, string $role): bool
    {
        return (self::TRANSITIONS[$from][$to] ?? null) === $role;
    }

    public static function assertCanMove(string $from, string $to, string $role): void
    {
        if (!isset(self::TRANSITIONS[$from])) {
            throw new ApiException('Estado desconocido.', 422);
        }
        if (self::TRANSITIONS[$from] === []) {
            throw new ApiException('La donación ya está cerrada y no puede cambiar.', 422);
        }
        if (!self::canMove($from, $to, $role)) {
            $de = Catalog::STATUSES[$from] ?? $from;
            $a = Catalog::STATUSES[$to] ?? $to;
            throw new ApiException("No se puede pasar de {$de} a {$a}.", 422);
        }
    }

    public static function isFinal(string $status): bool
    {
        return (self::TRANSITIONS[$status] ?? []) === [];
    }
}
