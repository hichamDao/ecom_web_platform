<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF des formulaires web.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function check(?string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY);
        if (!is_string($stored) || $token === null || $token === '') {
            return false;
        }

        return hash_equals($stored, $token);
    }

    /** Vérifie la requête courante : lève une 419 si le jeton est invalide. */
    public static function verifyRequest(?Request $request = null): void
    {
        $request ??= Request::instance();
        if (!$request->isMethod('POST')) {
            return;
        }
        if (!self::check($request->input('_token'))) {
            throw new HttpException('Jeton de sécurité invalide, veuillez recharger la page.', 419);
        }
    }

    public static function rotate(): void
    {
        Session::forget(self::SESSION_KEY);
    }
}
