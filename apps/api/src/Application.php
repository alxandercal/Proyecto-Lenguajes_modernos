<?php
declare(strict_types=1);

namespace ReFood;

// Enrutador. En el paso 0 solo responde /health para comprobar que la API enciende.
// Sebastián lo reemplaza completo en su PR "API de perfiles y donaciones".
final class Application
{
    public static function handle(): void
    {
        try {
            Http::cors();
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            if ($method === 'GET' && $path === 'api/v1/health') {
                Http::json(['ok' => true, 'service' => 'refood-api', 'version' => 'P0']);
            }
            throw new ApiException('Ruta no encontrada.', 404);
        } catch (ApiException $e) {
            Http::json(['error' => $e->getMessage()], $e->httpStatus);
        } catch (\Throwable $e) {
            error_log('ReFood API: ' . $e->getMessage());
            Http::json(['error' => 'Error interno del servidor.'], 500);
        }
    }
}
