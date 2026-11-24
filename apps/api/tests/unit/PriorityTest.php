<?php
declare(strict_types=1);

use ReFood\Domain\Priority;

$now = new DateTimeImmutable('2026-11-20T12:00:00Z');
$donation = static fn (string $expiresAt, string $zone, float $kg): array => [
    'expiresAt' => $expiresAt,
    'zone' => $zone,
    'quantityKg' => $kg,
];

return [
    'Ejemplo a mano: 5 kg que vencen en 24 h en la misma zona dan 69' => function () use ($now, $donation): void {
        $result = Priority::score($donation('2026-11-21T12:00:00Z', 'GUANAJUATO', 5), 'GUANAJUATO', $now);
        same(36.4, $result['factors'][0]['points'], 'Urgencia');
        same(30.0, $result['factors'][1]['points'], 'Cercanía');
        same(2.5, $result['factors'][2]['points'], 'Volumen');
        same(69, $result['score']);
    },
    'Lo que vence en 3 h va antes que lo que vence en 3 días' => function () use ($now, $donation): void {
        $ranked = Priority::rank([
            $donation('2026-11-23T12:00:00Z', 'LEON', 10) + ['id' => 'lejos'],
            $donation('2026-11-20T15:00:00Z', 'LEON', 10) + ['id' => 'pronto'],
        ], 'LEON', $now);
        same('pronto', $ranked[0]['id']);
    },
    'Con el mismo vencimiento, gana la más cercana' => function () use ($now, $donation): void {
        $ranked = Priority::rank([
            $donation('2026-11-21T12:00:00Z', 'CELAYA', 10) + ['id' => 'celaya'],
            $donation('2026-11-21T12:00:00Z', 'SILAO', 10) + ['id' => 'silao'],
        ], 'GUANAJUATO', $now);
        same('silao', $ranked[0]['id']);
    },
    'El puntaje queda entre 0 y 100 y trae 3 factores' => function () use ($now, $donation): void {
        foreach ([[1, 'LEON', 900], [500, 'DOLORES', 0.1]] as [$hours, $zone, $kg]) {
            $expires = $now->modify("+{$hours} hours")->format('Y-m-d\TH:i:s\Z');
            $result = Priority::score($donation($expires, $zone, $kg), 'CELAYA', $now);
            check($result['score'] >= 0 && $result['score'] <= 100, 'Fuera de rango');
            same(3, count($result['factors']));
        }
    },
    'Sin zona del receptor la cercanía vale la mitad' => function (): void {
        same(15.0, Priority::proximity(null));
    },
    'Los mismos datos dan siempre el mismo resultado' => function () use ($now, $donation): void {
        $input = $donation('2026-11-22T08:00:00Z', 'IRAPUATO', 12);
        same(Priority::score($input, 'LEON', $now), Priority::score($input, 'LEON', $now));
    },
];
