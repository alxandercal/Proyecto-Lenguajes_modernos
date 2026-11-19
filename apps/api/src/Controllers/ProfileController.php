<?php
declare(strict_types=1);

namespace ReFood\Controllers;

use ReFood\Auth;
use ReFood\Domain\Catalog;
use ReFood\Http;
use ReFood\Validator;

// POST /profiles y GET /me
final class ProfileController extends Controller
{
    // Se llama justo después de crear la cuenta en Firebase Authentication.
    public function create(): never
    {
        $user = Auth::identity($this->token);
        $body = Http::body();
        $profile = [
            'uid' => $user['uid'],
            'email' => $user['email'],
            'name' => Validator::text($body, 'name', 2, 80),
            'role' => Validator::oneOf($body, 'role', ['DONOR', 'RECEIVER']),
            'organizationName' => Validator::text($body, 'organizationName', 2, 100),
            'zone' => Validator::oneOf($body, 'zone', array_keys(Catalog::ZONES)),
            'createdAt' => $this->nowIso(),
        ];
        $saved = $this->db->create('users', $user['uid'], $profile, (string) $this->token);
        Http::json(['data' => $saved], 201);
    }

    public function me(): never
    {
        Http::json(['data' => Auth::profile($this->token)]);
    }
}
