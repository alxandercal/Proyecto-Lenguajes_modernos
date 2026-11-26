<?php
declare(strict_types=1);

use ReFood\Validator;

$now = new DateTimeImmutable('2026-11-20T12:00:00Z');

return [
    'El texto se limpia de espacios de sobra' => function (): void {
        same('Pan dulce', Validator::text(['title' => '  Pan   dulce '], 'title', 3, 80));
    },
    'Un título muy corto se rechaza con 422' => function (): void {
        throwsApi(fn () => Validator::text(['title' => 'ab'], 'title', 3, 80), 422);
    },
    'La cantidad 0 o negativa se rechaza' => function (): void {
        throwsApi(fn () => Validator::number(['quantity' => 0], 'quantity', 0.1, 5000), 422);
        throwsApi(fn () => Validator::number(['quantity' => -3], 'quantity', 0.1, 5000), 422);
    },
    'Una fecha que ya pasó se rechaza' => function () use ($now): void {
        throwsApi(fn () => Validator::futureDate(['expiresAt' => '2026-11-19T10:00:00Z'], 'expiresAt', $now), 422);
    },
    'Una fecha futura se guarda en UTC' => function () use ($now): void {
        same(
            '2026-11-21T00:00:00Z',
            Validator::futureDate(['expiresAt' => '2026-11-20T18:00:00-06:00'], 'expiresAt', $now)
        );
    },
    'Un identificador con ../ se rechaza con 400' => function (): void {
        throwsApi(fn () => Validator::documentId('../users'), 400);
    },
    'El enlace de evidencia debe ser https' => function (): void {
        throwsApi(fn () => Validator::optionalUrl(['evidenceUrl' => 'http://foto.mx/a.jpg'], 'evidenceUrl'), 422);
        same('', Validator::optionalUrl([], 'evidenceUrl'));
    },
];
