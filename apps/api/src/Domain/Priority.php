<?php
declare(strict_types=1);

namespace ReFood\Domain;

// Componente innovador: prioridad de recuperación de 0 a 100.
// Suma tres factores y guarda el porqué de cada uno para mostrarlo en pantalla.
final class Priority
{
    public const URGENCY_MAX = 50;   // qué tan pronto vence
    public const PROXIMITY_MAX = 30; // qué tan cerca está del receptor
    public const VOLUME_MAX = 20;    // cuánto alimento es

    public const URGENT_HOURS = 6;   // a 6 horas o menos la urgencia es total
    public const WINDOW_HOURS = 72;  // a 72 horas o más la urgencia vale 0
    public const FAR_KM = 80;        // a 80 km o más la cercanía vale 0
    public const FULL_KG = 40;       // con 40 kg o más el volumen es total

    public static function urgency(float $hoursLeft): float
    {
        if ($hoursLeft < 0) {
            return 0.0;
        }
        if ($hoursLeft <= self::URGENT_HOURS) {
            return self::URGENCY_MAX;
        }
        if ($hoursLeft >= self::WINDOW_HOURS) {
            return 0.0;
        }
        $span = self::WINDOW_HOURS - self::URGENT_HOURS;
        return round(self::URGENCY_MAX * (self::WINDOW_HOURS - $hoursLeft) / $span, 1);
    }

    public static function proximity(?float $km): float
    {
        if ($km === null) {
            return self::PROXIMITY_MAX / 2; // sin zona de referencia: valor medio
        }
        return round(self::PROXIMITY_MAX * max(0.0, 1 - $km / self::FAR_KM), 1);
    }

    public static function volume(float $kg): float
    {
        return round(self::VOLUME_MAX * min(1.0, max(0.0, $kg) / self::FULL_KG), 1);
    }

    /**
     * Calcula la prioridad de una donación.
     * $donation necesita expiresAt, zone y quantityKg.
     */
    public static function score(array $donation, ?string $viewerZone, \DateTimeImmutable $now): array
    {
        $expires = new \DateTimeImmutable((string) ($donation['expiresAt'] ?? 'now'));
        $hoursLeft = ($expires->getTimestamp() - $now->getTimestamp()) / 3600;
        $km = $viewerZone ? Catalog::distanceKm($viewerZone, (string) ($donation['zone'] ?? '')) : null;
        $kg = (float) ($donation['quantityKg'] ?? 0);

        $u = self::urgency($hoursLeft);
        $p = self::proximity($km);
        $v = self::volume($kg);

        return [
            'score' => (int) round($u + $p + $v),
            'factors' => [
                [
                    'key' => 'urgency',
                    'label' => 'Urgencia',
                    'points' => $u,
                    'max' => self::URGENCY_MAX,
                    'detail' => self::hoursText($hoursLeft),
                ],
                [
                    'key' => 'proximity',
                    'label' => 'Cercanía',
                    'points' => $p,
                    'max' => self::PROXIMITY_MAX,
                    'detail' => $km === null ? 'Sin zona de referencia' : 'A ' . $km . ' km de tu zona',
                ],
                [
                    'key' => 'volume',
                    'label' => 'Volumen',
                    'points' => $v,
                    'max' => self::VOLUME_MAX,
                    'detail' => 'Equivale a ' . $kg . ' kg',
                ],
            ],
        ];
    }

    /** Agrega 'priority' a cada donación y las ordena de mayor a menor. */
    public static function rank(array $donations, ?string $viewerZone, \DateTimeImmutable $now): array
    {
        foreach ($donations as $i => $donation) {
            $donations[$i]['priority'] = self::score($donation, $viewerZone, $now);
        }
        usort($donations, static function (array $a, array $b): int {
            $byScore = $b['priority']['score'] <=> $a['priority']['score'];
            return $byScore !== 0 ? $byScore : strcmp($a['expiresAt'], $b['expiresAt']);
        });
        return $donations;
    }

    private static function hoursText(float $hours): string
    {
        if ($hours < 0) {
            return 'Ya venció';
        }
        if ($hours < 1) {
            return 'Vence en menos de 1 hora';
        }
        if ($hours < 48) {
            return 'Vence en ' . (int) floor($hours) . ' h';
        }
        return 'Vence en ' . (int) floor($hours / 24) . ' días';
    }
}
