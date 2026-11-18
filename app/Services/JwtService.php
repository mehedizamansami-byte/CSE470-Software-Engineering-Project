<?php

namespace App\Services;

use App\Models\User;

/**
 * SERVICE layer: JSON Web Tokens (HS256) for the API login (Feature 1).
 * Tokens are signed with the APP_KEY, so no extra package is needed.
 */
class JwtService
{
    public function issue(User $user, int $ttlMinutes = 60): string
    {
        $now = time();

        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $user->id,
            'role' => $user->role,
            'iat' => $now,
            'exp' => $now + $ttlMinutes * 60,
        ];

        $unsigned = $this->encode(json_encode($header)).'.'.$this->encode(json_encode($payload));

        return $unsigned.'.'.$this->encode(hash_hmac('sha256', $unsigned, $this->key(), true));
    }

    /** Returns the payload of a valid, unexpired token, or null. */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expected = $this->encode(hash_hmac('sha256', "{$header}.{$payload}", $this->key(), true));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $head = json_decode($this->decodePart($header), true);
        $data = json_decode($this->decodePart($payload), true);

        if (($head['alg'] ?? null) !== 'HS256' || ! is_array($data) || ($data['exp'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }

    private function key(): string
    {
        $key = (string) config('app.key');

        return str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7)) : $key;
    }

    private function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function decodePart(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
