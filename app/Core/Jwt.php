<?php
declare(strict_types=1);

namespace App\Core;

/**
 * JWT minimal (HS256) pour authentifier l'application mobile ecom_app_fend.
 */
final class Jwt
{
    public static function encode(array $payload, int $ttl = 604800, ?string $secret = null, ?string $issuer = null): string
    {
        $secret = $secret ?? (string) config('jwt.secret', 'secret');
        $issuer = $issuer ?? (string) config('jwt.issuer', 'ecom_web_platform');
        $now = time();

        $payload += [
            'iss' => $issuer,
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        $header = self::base64UrlEncode((string) json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $claims = self::base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $claims, $secret, true)
        );

        return $header . '.' . $claims . '.' . $signature;
    }

    /** @return array<string, mixed>|null null si le token est invalide ou expiré */
    public static function decode(?string $token, ?string $secret = null): ?array
    {
        if ($token === null || $token === '') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $claims, $signature] = $parts;
        $secret = $secret ?? (string) config('jwt.secret', 'secret');
        $expected = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $claims, $secret, true));

        if (!hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($claims), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
