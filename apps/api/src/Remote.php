<?php
declare(strict_types=1);

namespace ReFood;

// Cliente HTTP con cURL para hablar con Firebase. Traduce los errores de Firebase
// a errores entendibles para el front y deja el detalle en la terminal de la API.
final class Remote
{
    public static function request(string $method, string $url, ?array $body = null, ?string $token = null): array
    {
        if (!function_exists('curl_init')) {
            throw new ApiException('PHP necesita la extensión cURL.', 503);
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new ApiException('No se pudo iniciar cURL.', 503);
        }
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            // linea pq windows verdad
            CURLOPT_CAINFO => __DIR__ . '/../certs/cacert.pem',
        ]);
        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_THROW_ON_ERROR));
        }
        $raw = curl_exec($curl);
        $code = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            error_log('ReFood Firebase: ' . curl_error($curl));
            throw new ApiException('No hay conexión con Firebase.', 502);
        }
        $decoded = json_decode($raw === '' ? '{}' : $raw, true);
        if ($code >= 400) {
            self::fail($code, is_array($decoded) ? $decoded : [], $method, $url);
        }
        if (!is_array($decoded)) {
            throw new ApiException('Firebase respondió algo inesperado.', 502);
        }
        return $decoded;
    }

    private static function fail(int $code, array $decoded, string $method, string $url): never
    {
        $status = (string) ($decoded['error']['status'] ?? '');
        $message = (string) ($decoded['error']['message'] ?? '');
        $path = (string) parse_url($url, PHP_URL_PATH);
        error_log("ReFood Firebase {$code} {$status} {$method} {$path}: {$message}");

        if ($code === 401 || $code === 403) {
            throw new ApiException('Acceso denegado por Firebase.', 403);
        }
        if ($code === 404) {
            throw new ApiException('Registro no encontrado.', 404);
        }
        if ($code === 409 || $status === 'FAILED_PRECONDITION' || $status === 'ALREADY_EXISTS') {
            throw new ApiException('El registro ya existe o cambió mientras lo guardabas.', 409);
        }
        throw new ApiException('Firebase no pudo atender la petición (' . $code . ').', 502);
    }
}