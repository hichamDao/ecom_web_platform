<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Bannière promotionnelle affichée en page d'accueil.
 */
final class PromoBanner extends Model
{
    protected static string $table = 'promo_banners';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'title', 'subtitle', 'photo', 'link', 'button_text', 'color_hex', 'position', 'is_active',
    ];

    protected static array $casts = [
        'id'        => 'int',
        'position'  => 'int',
        'is_active' => 'bool',
    ];

    /** @return array<int, self> */
    public static function allOrdered(): array
    {
        return self::rows('SELECT * FROM promo_banners ORDER BY position ASC, id ASC');
    }

    /** @return array<int, self> */
    public static function active(): array
    {
        return self::rows('SELECT * FROM promo_banners WHERE is_active = 1 ORDER BY position ASC, id ASC');
    }

    /**
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function fromPayload(array $data): array
    {
        return [
            'title'       => trim((string) ($data['title'] ?? '')),
            'subtitle'    => $data['subtitle'] ?? null,
            'photo'       => $data['photo'] ?? null,
            'link'        => $data['link'] ?? null,
            'button_text' => trim((string) ($data['buttonText'] ?? $data['button_text'] ?? 'Découvrir')),
            'color_hex'   => $data['colorHex'] ?? $data['color_hex'] ?? null,
            'position'    => (int) ($data['position'] ?? 0),
            'is_active'   => (bool) ($data['isActive'] ?? $data['is_active'] ?? true),
        ];
    }

    /** Met à jour l'ordre d'affichage. */
    public static function reorder(array $ids): void
    {
        $position = 1;
        foreach ($ids as $id) {
            self::db()->exec('UPDATE promo_banners SET position = ' . $position . ' WHERE id = ' . (int) $id);
            $position++;
        }
    }

    public static function nextPosition(): int
    {
        return (int) self::value('SELECT COALESCE(MAX(position), 0) + 1 FROM promo_banners', [], 1);
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'         => (int) $this->id,
            'title'      => (string) $this->title,
            'subtitle'   => (string) $this->subtitle,
            'photo'      => $this->photo,
            'link'       => $this->link,
            'buttonText' => (string) $this->button_text,
            'colorHex'   => $this->color_hex,
            'isActive'   => (bool) $this->is_active,
            'position'   => (int) $this->position,
        ];
    }
}
