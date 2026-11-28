<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\ApiException;
use ReFood\Auth;
use ReFood\Domain\DonationStatus;
use ReFood\Http;
use ReFood\Validator;

// Confirmación de entrega con evidencia. Cierra el flujo donación -> reserva -> entrega.
final class PickupController extends Controller
{
    // POST /pickups  (DONOR dueño)
    // body: {"donationId", "receivedBy", "note", "evidenceUrl"}
    public function create(): never
    {
        $profile = Auth::role($this->token, 'DONOR');
        $body = Http::body();
        $id = Validator::documentId(Validator::text($body, 'donationId', 6, 160));
        $receivedBy = Validator::text($body, 'receivedBy', 3, 80);
        $note = Validator::optionalText($body, 'note', 300);
        $evidenceUrl = Validator::optionalUrl($body, 'evidenceUrl');

        $donation = $this->findDonation($id);
        $this->assertOwner($donation, $profile);
        DonationStatus::assertCanMove($donation['status'], DonationStatus::DELIVERED, 'DONOR');

        $now = $this->nowIso();
        $kg = (float) $donation['quantityKg'];
        $pickup = [
            'donationId' => $id,
            'donorId' => $profile['uid'],
            'donorName' => $donation['donorName'],
            'receiverId' => $donation['reservedBy'],
            'receiverName' => $donation['reservedByName'],
            'donationTitle' => $donation['title'],
            'category' => $donation['category'],
            'zone' => $donation['zone'],
            'quantityKg' => $kg,
            'receivedBy' => $receivedBy,
            'note' => $note,
            'evidenceUrl' => $evidenceUrl,
            'deliveredAt' => $now,
        ];
        $history = [...$donation['history'], $this->historyEntry(DonationStatus::DELIVERED, $profile, $note)];

        // Todo junto: evidencia, donación entregada, reserva cerrada y total de kilos.
        $this->db->commit([
            $this->db->createWrite('pickups', $id, $pickup),
            $this->db->updateWrite('donations', $id, [
                'status' => DonationStatus::DELIVERED,
                'history' => $history,
                'updatedAt' => $now,
            ]),
            $this->db->updateWrite('reservations', $id, ['status' => 'DELIVERED', 'updatedAt' => $now]),
            $this->db->incrementWrite(
                'impactMetrics',
                'global',
                ['lastPickupId' => $id, 'updatedAt' => $now],
                ['deliveries' => 1, 'kgRecovered' => $kg]
            ),
        ], (string) $this->token);

        $pickup['id'] = $id;
        Http::json(['data' => $pickup], 201);
    }

    // GET /pickups/{id}  (donador, receptor o ADMIN)
    public function show(string $id): never
    {
        $profile = Auth::profile($this->token);
        $pickup = $this->db->get('pickups', Validator::documentId($id), $this->token);
        if ($pickup === null) {
            throw new ApiException('Esta donación todavía no tiene entrega registrada.', 404);
        }
        $allowed = in_array($profile['uid'], [$pickup['donorId'], $pickup['receiverId']], true)
            || $profile['role'] === 'ADMIN';
        if (!$allowed) {
            throw new ApiException('No tienes acceso a esta evidencia.', 403);
        }
        Http::json(['data' => $pickup]);
    }
}
