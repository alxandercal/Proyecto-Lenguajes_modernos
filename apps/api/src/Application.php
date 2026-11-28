<?php
declare(strict_types=1);

namespace ReFood;

use ReFood\Controllers\DonationController;
use ReFood\Controllers\MetricsController;
use ReFood\Controllers\PickupController;
use ReFood\Controllers\ProfileController;
use ReFood\Controllers\ReservationController;
use ReFood\Domain\Catalog;

// Enrutador: decide qué controlador atiende cada ruta y convierte los errores en JSON.
final class Application
{
    // [método, ruta, controlador, función]. {id} acepta cualquier identificador.
    private const ROUTES = [
        ['POST', 'profiles', ProfileController::class, 'create'],
        ['GET', 'me', ProfileController::class, 'me'],
        ['GET', 'donations', DonationController::class, 'list'],
        ['POST', 'donations', DonationController::class, 'create'],
        ['GET', 'donor/donations', DonationController::class, 'mine'],
        ['GET', 'donations/{id}', DonationController::class, 'show'],
        ['PATCH', 'donations/{id}', DonationController::class, 'update'],
        ['DELETE', 'donations/{id}', DonationController::class, 'delete'],
        ['POST', 'reservations', ReservationController::class, 'create'],
        ['GET', 'reservations/mine', ReservationController::class, 'mine'],
        ['DELETE', 'reservations/{id}', ReservationController::class, 'release'],
        ['POST', 'pickups', PickupController::class, 'create'],
        ['GET', 'pickups/{id}', PickupController::class, 'show'],
        ['GET', 'metrics/mine', MetricsController::class, 'mine'],
        ['GET', 'metrics', MetricsController::class, 'global'],
    ];

    public static function handle(): void
    {
        try {
            Http::cors();
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            if (!str_starts_with($path . '/', 'api/v1/')) {
                throw new ApiException('Ruta no encontrada.', 404);
            }
            $route = trim(substr($path, strlen('api/v1')), '/');

            if ($method === 'GET' && $route === 'health') {
                Http::json(['ok' => true, 'service' => 'refood-api', 'version' => 'R3']);
            }
            if ($method === 'GET' && $route === 'catalog') {
                Http::json(['data' => Catalog::toArray()]);
            }
            self::dispatch($method, $route);
        } catch (ApiException $e) {
            Http::json(['error' => $e->getMessage()], $e->httpStatus);
        } catch (\Throwable $e) {
            error_log('ReFood API: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            Http::json(['error' => 'Error interno del servidor.'], 500);
        }
    }

    private static function dispatch(string $method, string $route): never
    {
        $segments = $route === '' ? [] : explode('/', $route);
        $pathExists = false;
        foreach (self::ROUTES as [$verb, $pattern, $class, $action]) {
            $parts = explode('/', $pattern);
            if (count($parts) !== count($segments)) {
                continue;
            }
            $args = [];
            foreach ($parts as $i => $part) {
                if ($part === '{id}') {
                    $args[] = $segments[$i];
                } elseif ($part !== $segments[$i]) {
                    continue 2;
                }
            }
            $pathExists = true;
            if ($verb === $method) {
                (new $class(Http::token()))->$action(...$args);
            }
        }
        throw $pathExists
            ? new ApiException('Método no permitido en esta ruta.', 405)
            : new ApiException('Ruta no encontrada.', 404);
    }
}
