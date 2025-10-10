<?php

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class VoteJwt
{
    public static function issue(array $claims, int $ttlSeconds = 900): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => config('app.url'),
            'iat' => $now,
            'nbf' => $now - 1,
            'exp' => $now + $ttlSeconds,
            'jti' => bin2hex(random_bytes(16)),
        ], $claims);

        return JWT::encode($payload, self::secret(), self::algo());
    }

    public static function verify(string $jwt): array
    {
        $decoded = JWT::decode($jwt, new Key(self::secret(), self::algo()));
        return json_decode(json_encode($decoded), true);
    }

    private static function secret(): string
    {
        $secret = env('VOTE_JWT_SECRET');
        if (!$secret) throw new \RuntimeException('VOTE_JWT_SECRET missing');
        return $secret;
    }

    private static function algo(): string
    {
        return env('VOTE_JWT_ALGO', 'HS256');
    }
}
