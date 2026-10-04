<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Paramètres clé/valeur du site (cache en mémoire pour la requête courante).
 */
final class Setting extends Model
{
    protected static string $table = 'settings';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['setting_key', 'setting_value'];

    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /** @return array<string, string|null> */
    public static function allSettings(): array
    {
        if (self::$cache === null) {
            $settings = [];
            foreach (self::db()->query('SELECT setting_key, setting_value FROM settings')->fetchAll() as $row) {
                $settings[(string) $row['setting_key']] = $row['setting_value'] === null ? null : (string) $row['setting_value'];
            }
            self::$cache = $settings;
        }

        return self::$cache;
    }

    /**
     * Lit un paramètre.
     *
     * Volontairement nommée read() et non get() : Model déclare déjà
     * get(string $key, mixed $default) comme méthode d'instance.
     */
    public static function read(string $key, mixed $default = null): mixed
    {
        $settings = self::allSettings();

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        $exists = (int) self::value(
            'SELECT COUNT(*) FROM settings WHERE setting_key = :key',
            ['key' => $key],
            0
        ) > 0;

        if ($exists) {
            self::db()->exec(
                "UPDATE settings SET setting_value = " . self::db()->quote($value) . ' WHERE setting_key = ' . self::db()->quote($key)
            );
        } else {
            self::db()->exec(
                'INSERT INTO settings (setting_key, setting_value) VALUES ('
                . self::db()->quote($key) . ', ' . self::db()->quote($value) . ')'
            );
        }

        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
