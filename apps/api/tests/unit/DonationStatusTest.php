<?php
declare(strict_types=1);

use ReFood\Domain\DonationStatus as S;

return [
    'Un receptor puede reservar una donación publicada' => function (): void {
        check(S::canMove(S::PUBLISHED, S::RESERVED, 'RECEIVER'));
    },
    'Un donador no puede reservar' => function (): void {
        check(!S::canMove(S::PUBLISHED, S::RESERVED, 'DONOR'));
    },
    'Una donación reservada no admite una segunda reserva' => function (): void {
        check(!S::canMove(S::RESERVED, S::RESERVED, 'RECEIVER'));
        throwsApi(fn () => S::assertCanMove(S::RESERVED, S::RESERVED, 'RECEIVER'), 422);
    },
    'Solo el donador confirma la entrega de una reservada' => function (): void {
        check(S::canMove(S::RESERVED, S::DELIVERED, 'DONOR'));
        check(!S::canMove(S::PUBLISHED, S::DELIVERED, 'DONOR'));
        check(!S::canMove(S::RESERVED, S::DELIVERED, 'RECEIVER'));
    },
    'Entregada y Cancelada son estados finales' => function (): void {
        check(S::isFinal(S::DELIVERED) && S::isFinal(S::CANCELLED));
        throwsApi(fn () => S::assertCanMove(S::DELIVERED, S::PUBLISHED, 'DONOR'), 422);
    },
];
