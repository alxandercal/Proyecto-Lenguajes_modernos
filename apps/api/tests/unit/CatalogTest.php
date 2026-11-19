<?php
declare(strict_types=1);

use ReFood\Domain\Catalog;

return [
    'Un kilo vale un kilo y un litro también' => function (): void {
        same(5.0, Catalog::toKg(5, 'KG'));
        same(2.0, Catalog::toKg(2, 'L'));
    },
    'Las piezas y porciones se convierten a kilos' => function (): void {
        same(3.0, Catalog::toKg(12, 'PIEZA'));
        same(4.0, Catalog::toKg(10, 'PORCION'));
    },
    'La distancia de una zona a sí misma es 0 km' => function (): void {
        same(0.0, Catalog::distanceKm('LEON', 'LEON'));
    },
    'De Guanajuato capital a Silao hay entre 15 y 25 km' => function (): void {
        $km = Catalog::distanceKm('GUANAJUATO', 'SILAO');
        check($km !== null && $km > 15 && $km < 25, "Distancia inesperada: {$km}");
    },
    'Una zona que no existe no tiene distancia' => function (): void {
        same(null, Catalog::distanceKm('LEON', 'MARTE'));
    },
];
