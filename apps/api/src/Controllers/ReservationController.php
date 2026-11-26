<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\ApiException;
use ReFood\Auth;
use ReFood\Domain\DonationStatus;
use ReFood\Http;
use ReFood\Validator;

// Reserva única: el id de la reserva es el mismo id de la donación.
// Firestore no deja crear dos documentos con el mismo id, así que nunca hay dos reservas.
final class ReservationController extends Controller
{
    private const TAKEN = 'Esta donación ya fue reservada por otra organización.';

    // POST /reservations  (RECEIVER)  body: {"donationId": "..."}
    public function create(): never
    {
        $profile = Auth::role($this->token, 'RECEIVER');
        $id = Validator::documentId(Validator::text(Http::body(), 'donationId', 6, 160));
        try {
            $donation = $this->findDonation($id);
        } catch (ApiException $e) {
            // Las reglas no dejan leer donaciones reservadas por otro: ya no está disponible.
            throw $e->httpStatus === 403 ? new ApiException(self::TAKEN, 409) : $e;
        }
        $this->assertReservable($donation);

        $now = $this->nowIso();
        $reservation = [
            'donationId' => $id,
            'donorId' => $donation['ownerId'],
            'donorName' => $donation['donorName'],
            'receiverId' => $profile['uid'],
            'receiverName' => $profile['organizationName'],
            'donationTitle' => $donation['title'],
            'quantity' => (float) $donation['quantity'],
            'unit' => $donation['unit'],
            'quantityKg' => (float) $donation['quantityKg'],
            'zone' => $donation['zone'],
            'pickupAddress' => $donation['pickupAddress'],
            'expiresAt' => $donation['expiresAt'],
            'status' => 'ACTIVE',
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
        $changes = [
            'status' => DonationStatus::RESERVED,
            'reservedBy' => $profile['uid'],
            'reservedByName' => $profile['organizationName'],
            'history' => [...$donation['history'], $this->historyEntry(DonationStatus::RESERVED, $profile)],
            'updatedAt' => $now,
        ];

        try {
            $this->db->commit([
                $this->db->createWrite('reservations', $id, $reservation),
                $this->db->updateWrite('donations', $id, $changes),
            ], (string) $this->token);
        } catch (ApiException $e) {
            // Si otra organización ganó la reserva un instante antes, se responde 409.
            throw $this->stillPublished($id) ? $e : new ApiException(self::TAKEN, 409);
        }
        $reservation['id'] = $id;
        Http::json(['data' => $reservation], 201);
    }

    // GET /reservations/mine  (RECEIVER)
    public function mine(): never
    {
        $profile = Auth::role($this->token, 'RECEIVER');
        $items = $this->db->query('reservations', ['receiverId' => $profile['uid']], $this->token);
        Http::json(['data' => self::newestFirst($items)]);
    }

    // DELETE /reservations/{id}  (RECEIVER dueño) -> libera la donación para otros
    public function release(string $id): never
    {
        $profile = Auth::role($this->token, 'RECEIVER');
        $reservation = $this->db->get('reservations', Validator::documentId($id), $this->token);
        if ($reservation === null || $reservation['receiverId'] !== $profile['uid']) {
            throw new ApiException('Esa reserva no existe o no es tuya.', 404);
        }
        if ($reservation['status'] !== 'ACTIVE') {
            throw new ApiException('Esa reserva ya está cerrada.', 422);
        }
        $donation = $this->findDonation($id);
        DonationStatus::assertCanMove($donation['status'], DonationStatus::PUBLISHED, 'RECEIVER');

        $this->db->commit([
            $this->db->deleteWrite('reservations', $id),
            $this->db->updateWrite('donations', $id, [
                'status' => DonationStatus::PUBLISHED,
                'reservedBy' => '',
                'reservedByName' => '',
                'history' => [...$donation['history'], $this->historyEntry('RELEASED', $profile)],
                'updatedAt' => $this->nowIso(),
            ]),
        ], (string) $this->token);
        Http::json(['data' => ['id' => $id, 'released' => true]]);
    }

    private function stillPublished(string $id): bool
    {
        try {
            $current = $this->db->get('donations', $id, $this->token);
            return $current !== null && $current['status'] === DonationStatus::PUBLISHED;
        } catch (ApiException) {
            return false;
        }
    }

    private function assertReservable(array $donation): void
    {
        if ($donation['status'] === DonationStatus::RESERVED) {
            throw new ApiException(self::TAKEN, 409);
        }
        DonationStatus::assertCanMove($donation['status'], DonationStatus::RESERVED, 'RECEIVER');
        if (strtotime((string) $donation['expiresAt']) <= $this->now->getTimestamp()) {
            throw new ApiException('Esta donación ya venció y no se puede reservar.', 422);
        }
    }
}
