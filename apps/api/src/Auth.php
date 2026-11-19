<?php
declare(strict_types=1);

namespace ReFood;

// Quién hace la petición: valida el token con Firebase y lee su perfil en users/{uid}.
final class Auth
{
    public const ROLES = ['DONOR', 'RECEIVER', 'ADMIN'];

    // Solo valida el token. Se usa al crear el perfil, cuando todavía no existe.
    public static function identity(?string $token): array
    {
        if ($token === null) {
            throw new ApiException('Debes iniciar sesión.', 401);
        }
        $url = Config::identityUrl() . '/accounts:lookup?key='
            . rawurlencode(Config::required('firebase_api_key'));
        try {
            $response = Remote::request('POST', $url, ['idToken' => $token]);
        } catch (ApiException $e) {
            if ($e->httpStatus >= 500) {
                throw $e;
            }
            throw new ApiException('Tu sesión venció. Vuelve a iniciar sesión.', 401);
        }
        $user = $response['users'][0] ?? null;
        if (!is_array($user) || empty($user['localId']) || !empty($user['disabled'])) {
            throw new ApiException('Usuario no autorizado.', 401);
        }
        return ['uid' => (string) $user['localId'], 'email' => (string) ($user['email'] ?? '')];
    }

    // Token válido + perfil creado.
    public static function profile(?string $token): array
    {
        $identity = self::identity($token);
        $profile = (new Firestore())->get('users', $identity['uid'], $token);
        if ($profile === null) {
            throw new ApiException('Completa tu registro antes de continuar.', 403);
        }
        if (($profile['uid'] ?? '') !== $identity['uid'] || !in_array($profile['role'] ?? '', self::ROLES, true)) {
            throw new ApiException('Tu perfil no es válido.', 403);
        }
        return $profile;
    }

    // Token válido + perfil + uno de los roles pedidos.
    public static function role(?string $token, string ...$roles): array
    {
        $profile = self::profile($token);
        if (!in_array($profile['role'], $roles, true)) {
            throw new ApiException('Tu tipo de cuenta no puede hacer esta operación.', 403);
        }
        return $profile;
    }
}
