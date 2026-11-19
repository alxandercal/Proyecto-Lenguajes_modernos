<?php
declare(strict_types=1);

namespace ReFood\Domain;

// Listas fijas del negocio. El front las pide a GET /catalog para no repetirlas.
final class Catalog
{
    public const CATEGORIES = [
        'PREPARADOS' => 'Comida preparada',
        'PANADERIA' => 'Pan y repostería',
        'FRUTAS_VERDURAS' => 'Frutas y verduras',
        'LACTEOS' => 'Lácteos y huevo',
        'ABARROTES' => 'Abarrotes',
        'BEBIDAS' => 'Bebidas',
        'OTROS' => 'Otros',
    ];

    // Cuántos kilos vale una unidad. Sirve para sumar kilos aunque se publique en piezas.
    public const UNITS = [
        'KG' => ['label' => 'Kilogramos', 'kg' => 1.0],
        'L' => ['label' => 'Litros', 'kg' => 1.0],
        'PIEZA' => ['label' => 'Piezas', 'kg' => 0.25],
        'PORCION' => ['label' => 'Porciones', 'kg' => 0.4],
    ];

    // Centro aproximado de cada zona. La distancia se mide entre estos puntos.
    public const ZONES = [
        'GUANAJUATO' => ['label' => 'Guanajuato capital', 'lat' => 21.0190, 'lng' => -101.2574],
        'LEON' => ['label' => 'León', 'lat' => 21.1250, 'lng' => -101.6860],
        'SILAO' => ['label' => 'Silao', 'lat' => 20.9434, 'lng' => -101.4270],
        'IRAPUATO' => ['label' => 'Irapuato', 'lat' => 20.6767, 'lng' => -101.3563],
        'SALAMANCA' => ['label' => 'Salamanca', 'lat' => 20.5730, 'lng' => -101.1950],
        'CELAYA' => ['label' => 'Celaya', 'lat' => 20.5222, 'lng' => -100.8122],
        'SAN_MIGUEL' => ['label' => 'San Miguel de Allende', 'lat' => 20.9144, 'lng' => -100.7452],
        'DOLORES' => ['label' => 'Dolores Hidalgo', 'lat' => 21.1561, 'lng' => -100.9325],
    ];

    public const STATUSES = [
        'PUBLISHED' => 'Publicada',
        'RESERVED' => 'Reservada',
        'DELIVERED' => 'Entregada',
        'CANCELLED' => 'Cancelada',
    ];

    public static function toKg(float $quantity, string $unit): float
    {
        $factor = self::UNITS[$unit]['kg'] ?? 1.0;
        return round($quantity * $factor, 2);
    }

    // Distancia en línea recta entre dos zonas (fórmula de Haversine).
    public static function distanceKm(string $from, string $to): ?float
    {
        if (!isset(self::ZONES[$from], self::ZONES[$to])) {
            return null;
        }
        $a = self::ZONES[$from];
        $b = self::ZONES[$to];
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $h = sin($dLat / 2) ** 2
            + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;
        return round(2 * 6371 * asin(min(1.0, sqrt($h))), 1);
    }

    // Lo que devuelve GET /catalog
    public static function toArray(): array
    {
        $list = static fn (array $items): array => array_map(
            static fn (string $value, mixed $item): array => [
                'value' => $value,
                'label' => is_array($item) ? $item['label'] : $item,
            ],
            array_keys($items),
            array_values($items)
        );
        $units = $list(self::UNITS);
        foreach ($units as $i => $unit) {
            $units[$i]['kg'] = self::UNITS[$unit['value']]['kg'];
        }
        return [
            'categories' => $list(self::CATEGORIES),
            'units' => $units,
            'zones' => $list(self::ZONES),
            'statuses' => $list(self::STATUSES),
        ];
    }
}
