<?php
declare(strict_types=1);

namespace ReFood;

// Lee config/local.php y deja que una variable de entorno con el mismo nombre
// en mayúsculas lo reemplace (así se configura el hosting sin subir secretos).
final class Config
{
    private static ?array $values = null;

    public static function get(string $key): mixed
    {
        if (self::$values === null) {
            $file = dirname(__DIR__) . '/config/local.php';
            self::$values = is_file($file) ? (require $file) : [];
        }
        $env = getenv(strtoupper($key));
        if ($env !== false && $env !== '') {
            return $env;
        }
        return self::$values[$key] ?? null;
    }

    public static function required(string $key): string
    {
        $value = self::get($key);
        if (!is_string($value) || $value === '' || str_contains($value, 'REEMPLAZAR')) {
            throw new ApiException("Configura {$key} en apps/api/config/local.php", 503);
        }
        return $value;
    }

    /** @return string[] */
    public static function allowedOrigins(): array
    {
        $value = self::get('allowed_origins') ?? ['http://localhost:3000', 'http://127.0.0.1:3000'];
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        return array_values(array_filter(array_map('trim', $value)));
    }

    // URL base de Firestore. Con FIRESTORE_EMULATOR_HOST se usa el emulador o el Firebase falso.
    public static function firestoreUrl(): string
    {
        $host = self::get('firestore_emulator_host');
        return $host ? 'http://' . $host . '/v1' : 'https://firestore.googleapis.com/v1';
    }

    // URL base de Authentication. Con FIREBASE_AUTH_EMULATOR_HOST se usa el emulador.
    public static function identityUrl(): string
    {
        $host = self::get('firebase_auth_emulator_host');
        return $host
            ? 'http://' . $host . '/identitytoolkit.googleapis.com/v1'
            : 'https://identitytoolkit.googleapis.com/v1';
    }
}
