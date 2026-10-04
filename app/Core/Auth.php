<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentification double canal :
 *  - API mobile  : jeton JWT dans l'en-tête Authorization: Bearer ...
 *  - Boutique web: session PHP (utilisateur_id)
 */
final class Auth
{
    private static ?User $resolved = null;
    private static bool $resolvedFlag = false;

    // -----------------------------------------------------------------
    // API (JWT)
    // -----------------------------------------------------------------

    public static function attempt(string $email, string $password): ?User
    {
        $user = User::findBy('email', mb_strtolower(trim($email)));
        if ($user === null || (int) $user->is_active !== 1) {
            return null;
        }
        if (!password_verify($password, (string) $user->password)) {
            return null;
        }

        $user->touchLastLogin();

        return $user;
    }

    public static function issueToken(User $user): string
    {
        return Jwt::encode([
            'sub'   => (int) $user->id,
            'email' => $user->email,
            'role'  => $user->role,
        ]);
    }

    public static function userFromToken(?string $token): ?User
    {
        $payload = Jwt::decode($token);
        if ($payload === null || !isset($payload['sub'])) {
            return null;
        }

        return User::find((int) $payload['sub']);
    }

    // -----------------------------------------------------------------
    // Web (session)
    // -----------------------------------------------------------------

    public static function loginWeb(User $user): void
    {
        $user->touchLastLogin();

        Session::regenerate();
        Session::set('user_id', (int) $user->id);
        self::$resolved = $user;
        self::$resolvedFlag = true;
    }

    public static function logoutWeb(): void
    {
        Session::forget('user_id');
        self::$resolved = null;
        self::$resolvedFlag = true;
    }

    // -----------------------------------------------------------------
    // Accès commun
    // -----------------------------------------------------------------

    public static function user(): ?User
    {
        if (self::$resolvedFlag) {
            return self::$resolved;
        }

        self::$resolvedFlag = true;
        $user = self::userFromToken(Request::instance()->bearerToken());
        if ($user === null) {
            $id = Session::get('user_id');
            if ($id !== null) {
                $user = User::find((int) $id);
            }
        }
        self::$resolved = $user;

        return $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user->id;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && strtoupper((string) $user->role) === 'ADMIN';
    }

    public static function guest(): void
    {
        if (self::check()) {
            Response::redirect('/compte');
        }
    }

    public static function logout(): void
    {
        self::logoutWeb();
    }

    /** Réinitialise le cache utilisateur (utile dans les tests). */
    public static function flush(): void
    {
        self::$resolved = null;
        self::$resolvedFlag = false;
    }
}
