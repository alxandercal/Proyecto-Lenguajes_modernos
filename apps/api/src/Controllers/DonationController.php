<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\ApiException;
use ReFood\Auth;
use ReFood\Domain\Catalog;
use ReFood\Domain\DonationStatus;
use ReFood\Domain\Priority;
use ReFood\Http;
use ReFood\Validator;

// Donaciones: publicar, listar con filtros y prioridad, y ver el detalle.
// En la R2 se agregan editar, cancelar y borrar.
final class DonationController extends Controller
{
    // GET /donations  (público) -> publicadas, con filtros y ordenadas por prioridad
    public function list(): never
    {
        $category = Http::query('category');
        $zone = Http::query('zone');
        $text = Http::query('q');
        $validity = Http::query('validity') ?? 'valid'; // valid | 24h | all
        $now = $this->now->getTimestamp();

        $items = $this->db->query('donations', ['status' => DonationStatus::PUBLISHED], $this->token);
        $items = array_filter($items, static function (array $d) use ($category, $zone, $text, $validity, $now) {
            $hoursLeft = (strtotime((string) $d['expiresAt']) - $now) / 3600;
            if ($validity !== 'all' && $hoursLeft <= 0) {
                return false;
            }
            if ($validity === '24h' && $hoursLeft > 24) {
                return false;
            }
            if ($category !== null && $d['category'] !== $category) {
                return false;
            }
            if ($zone !== null && $d['zone'] !== $zone) {
                return false;
            }
            if ($text !== null) {
                $haystack = $d['title'] . ' ' . $d['donorName'] . ' ' . ($d['notes'] ?? '');
                return mb_stripos($haystack, $text) !== false;
            }
            return true;
        });

        $viewer = $this->optionalProfile();
        $from = Http::query('from');
        $viewerZone = $from !== null && isset(Catalog::ZONES[$from]) ? $from : ($viewer['zone'] ?? null);
        Http::json(['data' => Priority::rank(array_values($items), $viewerZone, $this->now)]);
    }

    // POST /donations  (DONOR)
    public function create(): never
    {
        $profile = Auth::role($this->token, 'DONOR');
        $now = $this->nowIso();
        $donation = $this->validated(Http::body()) + [
            'ownerId' => $profile['uid'],
            'donorName' => $profile['organizationName'],
            'status' => DonationStatus::PUBLISHED,
            'reservedBy' => '',
            'reservedByName' => '',
            'history' => [$this->historyEntry(DonationStatus::PUBLISHED, $profile)],
            'createdAt' => $now,
            'updatedAt' => $now,
        ];
        $saved = $this->db->create('donations', $this->newId(), $donation, (string) $this->token);
        Http::json(['data' => $saved], 201);
    }

    // GET /donor/donations  (DONOR) -> todas las suyas, en cualquier estado
    public function mine(): never
    {
        $profile = Auth::role($this->token, 'DONOR');
        $items = $this->db->query('donations', ['ownerId' => $profile['uid']], $this->token);
        Http::json(['data' => self::newestFirst($items)]);
    }

    // GET /donations/{id}
    public function show(string $id): never
    {
        Validator::documentId($id);
        $viewer = $this->optionalProfile();
        try {
            $donation = $this->findDonation($id);
        } catch (ApiException $e) {
            if ($e->httpStatus === 403) {
                throw new ApiException('Esta donación ya no está disponible.', 403);
            }
            throw $e;
        }
        if ($donation['status'] !== DonationStatus::PUBLISHED) {
            if ($viewer === null) {
                throw new ApiException('Inicia sesión para ver esta donación.', 401);
            }
            $allowed = $donation['ownerId'] === $viewer['uid']
                || $donation['reservedBy'] === $viewer['uid']
                || $viewer['role'] === 'ADMIN';
            if (!$allowed) {
                throw new ApiException('Esta donación ya no está disponible.', 403);
            }
        }
        $from = Http::query('from');
        $zone = $from !== null && isset(Catalog::ZONES[$from]) ? $from : ($viewer['zone'] ?? null);
        $donation['priority'] = Priority::score($donation, $zone, $this->now);
        Http::json(['data' => $donation]);
    }

    // Campos que el donador captura, validados. Se usan al crear y al editar.
    private function validated(array $body): array
    {
        $quantity = Validator::number($body, 'quantity', 0.1, 5000);
        $unit = Validator::oneOf($body, 'unit', array_keys(Catalog::UNITS));
        return [
            'title' => Validator::text($body, 'title', 3, 80),
            'category' => Validator::oneOf($body, 'category', array_keys(Catalog::CATEGORIES)),
            'quantity' => $quantity,
            'unit' => $unit,
            'quantityKg' => Catalog::toKg($quantity, $unit),
            'expiresAt' => Validator::futureDate($body, 'expiresAt', $this->now),
            'pickupAddress' => Validator::text($body, 'pickupAddress', 5, 160),
            'zone' => Validator::oneOf($body, 'zone', array_keys(Catalog::ZONES)),
            'notes' => Validator::optionalText($body, 'notes', 300),
        ];
    }

    // Perfil de quien consulta, si mandó token. En rutas públicas no es obligatorio.
    private function optionalProfile(): ?array
    {
        if ($this->token === null) {
            return null;
        }
        try {
            return Auth::profile($this->token);
        } catch (ApiException) {
            return null;
        }
    }
}
