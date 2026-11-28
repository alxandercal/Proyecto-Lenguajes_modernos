<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\Auth;
use ReFood\Domain\Catalog;
use ReFood\Http;

// Indicadores de impacto: entregas y kilos recuperados.
final class MetricsController extends Controller
{
    // GET /metrics/mine  -> según el rol de quien pregunta
    public function mine(): never
    {
        $profile = Auth::profile($this->token);
        if ($profile['role'] === 'ADMIN') {
            $this->global();
        }
        $field = $profile['role'] === 'DONOR' ? 'donorId' : 'receiverId';
        $pickups = $this->db->query('pickups', [$field => $profile['uid']], $this->token);
        $data = ['role' => $profile['role']] + self::summarize($pickups);

        if ($profile['role'] === 'DONOR') {
            $donations = $this->db->query('donations', ['ownerId' => $profile['uid']], $this->token);
            $data['statusCounts'] = self::countBy($donations, 'status', array_keys(Catalog::STATUSES));
        } else {
            $active = $this->db->query(
                'reservations',
                ['receiverId' => $profile['uid'], 'status' => 'ACTIVE'],
                $this->token
            );
            $data['activeReservations'] = count($active);
        }
        Http::json(['data' => $data]);
    }

    // GET /metrics  (ADMIN) -> toda la plataforma
    public function global(): never
    {
        Auth::role($this->token, 'ADMIN');
        $counter = $this->db->get('impactMetrics', 'global', $this->token) ?? [];
        $pickups = $this->db->query('pickups', [], $this->token, 500);
        $donations = $this->db->query('donations', [], $this->token, 500);

        $donors = [];
        foreach ($pickups as $pickup) {
            $name = (string) $pickup['donorName'];
            $donors[$name] = round(($donors[$name] ?? 0) + (float) $pickup['quantityKg'], 2);
        }
        arsort($donors);
        $topDonors = [];
        foreach (array_slice($donors, 0, 5, true) as $name => $kg) {
            $topDonors[] = ['name' => $name, 'kg' => $kg];
        }

        $summary = self::summarize($pickups);
        Http::json(['data' => [
            'role' => 'ADMIN',
            // El contador oficial vive en impactMetrics/global y crece con cada entrega.
            'deliveries' => (int) ($counter['deliveries'] ?? $summary['deliveries']),
            'kgRecovered' => round((float) ($counter['kgRecovered'] ?? $summary['kgRecovered']), 2),
            'byCategory' => $summary['byCategory'],
            'byZone' => self::groupKg($pickups, 'zone', Catalog::ZONES),
            'statusCounts' => self::countBy($donations, 'status', array_keys(Catalog::STATUSES)),
            'topDonors' => $topDonors,
            'recentPickups' => array_slice(self::newestFirst($pickups, 'deliveredAt'), 0, 5),
        ]]);
    }

    private static function summarize(array $pickups): array
    {
        $kg = array_sum(array_map(static fn (array $p): float => (float) $p['quantityKg'], $pickups));
        return [
            'deliveries' => count($pickups),
            'kgRecovered' => round($kg, 2),
            'byCategory' => self::groupKg($pickups, 'category', Catalog::CATEGORIES),
        ];
    }

    // Suma kilos por categoría o por zona, con su nombre para mostrar.
    private static function groupKg(array $pickups, string $field, array $labels): array
    {
        $totals = [];
        foreach ($pickups as $pickup) {
            $key = (string) ($pickup[$field] ?? '');
            $totals[$key] = round(($totals[$key] ?? 0) + (float) $pickup['quantityKg'], 2);
        }
        arsort($totals);
        $result = [];
        foreach ($totals as $key => $kg) {
            $label = $labels[$key] ?? $key;
            $result[] = ['value' => $key, 'label' => is_array($label) ? $label['label'] : $label, 'kg' => $kg];
        }
        return $result;
    }

    private static function countBy(array $items, string $field, array $keys): array
    {
        $counts = array_fill_keys($keys, 0);
        foreach ($items as $item) {
            $key = (string) ($item[$field] ?? '');
            if (isset($counts[$key])) {
                $counts[$key]++;
            }
        }
        return $counts;
    }
}
