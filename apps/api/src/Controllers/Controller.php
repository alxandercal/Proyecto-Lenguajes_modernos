<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\ApiException;
use ReFood\Firestore;

// Lo que comparten todos los controladores: base de datos, token, hora actual e historial.
abstract class Controller
{
    protected Firestore $db;
    protected \DateTimeImmutable $now;

    public function __construct(protected ?string $token)
    {
        $this->db = new Firestore();
        $this->now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    protected function nowIso(): string
    {
        return $this->now->format('Y-m-d\TH:i:s\Z');
    }

    protected function newId(): string
    {
        return bin2hex(random_bytes(10));
    }

    // Un movimiento del historial de una donación.
    protected function historyEntry(string $action, array $profile, string $note = ''): array
    {
        return [
            'at' => $this->nowIso(),
            'action' => $action,
            'byUid' => (string) $profile['uid'],
            'byName' => (string) $profile['organizationName'],
            'note' => $note,
        ];
    }

    protected function findDonation(string $id): array
    {
        $donation = $this->db->get('donations', $id, $this->token);
        if ($donation === null) {
            throw new ApiException('La donación no existe.', 404);
        }
        return $donation;
    }

    protected function assertOwner(array $donation, array $profile): void
    {
        if (($donation['ownerId'] ?? '') !== $profile['uid']) {
            throw new ApiException('Esta donación no es tuya.', 403);
        }
    }

    // Ordena del más reciente al más antiguo según un campo de fecha.
    protected static function newestFirst(array $items, string $field = 'createdAt'): array
    {
        usort($items, static fn (array $a, array $b): int => strcmp($b[$field] ?? '', $a[$field] ?? ''));
        return $items;
    }
}
