<?php
declare(strict_types=1);

namespace ReFood;

// Todo lo que tiene que ver con la petición y la respuesta HTTP.
final class Http
{
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        exit;
    }

    // Cuerpo JSON de la petición. Siempre debe ser un objeto.
    public static function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || strlen($raw) > 32768) {
            throw new ApiException('El cuerpo de la petición es demasiado grande.', 413);
        }
        if (trim($raw) === '') {
            return [];
        }
        try {
            $data = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new ApiException('El JSON enviado no es válido.', 400);
        }
        if (!is_array($data) || ($data !== [] && array_is_list($data))) {
            throw new ApiException('Se esperaba un objeto JSON.', 400);
        }
        return $data;
    }

    // Parámetro de la URL (?zone=LEON). Devuelve null si no viene o viene vacío.
    public static function query(string $key): ?string
    {
        $value = $_GET[$key] ?? null;
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    // Token de Firebase que manda el front: "Authorization: Bearer <token>"
    public static function token(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+([A-Za-z0-9._-]+)$/', $header, $match)) {
            return $match[1];
        }
        return null;
    }

    public static function cors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '' && in_array($origin, Config::allowedOrigins(), true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            header('Access-Control-Max-Age: 600');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
