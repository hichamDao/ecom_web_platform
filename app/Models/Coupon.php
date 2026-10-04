<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Code promotionnel (pourcentage, restrictions produit, ciblage utilisateur).
 */
final class Coupon extends Model
{
    protected static string $table = 'coupons';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'code', 'percentage', 'min_order_amount', 'max_discount_amount',
        'usage_limit', 'used_count', 'product_ids', 'user_id', 'expires_at', 'is_active',
    ];

    protected static array $casts = [
        'id'                  => 'int',
        'percentage'          => 'float',
        'min_order_amount'    => 'float',
        'max_discount_amount' => 'float',
        'usage_limit'         => 'int',
        'used_count'          => 'int',
        'product_ids'         => 'json',
        'user_id'             => 'int',
        'is_active'           => 'bool',
    ];

    public static function findByCode(string $code): ?self
    {
        return self::row(
            'SELECT * FROM coupons WHERE code = :code LIMIT 1',
            ['code' => mb_strtoupper(trim($code))]
        );
    }

    /** @return array<int, self> */
    public static function allActive(): array
    {
        return self::rows('SELECT * FROM coupons WHERE is_active = 1 ORDER BY created_at DESC');
    }

    /** @return array<int, self> Codes globaux actifs + codes nominatifs de l'utilisateur. */
    public static function availableFor(int $userId): array
    {
        return self::rows(
            'SELECT * FROM coupons
             WHERE is_active = 1 AND (user_id IS NULL OR user_id = :userId)
             ORDER BY created_at DESC',
            ['userId' => $userId]
        );
    }

    public static function codeExists(string $code, ?int $exceptId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM coupons WHERE code = :code';
        $params = ['code' => mb_strtoupper(trim($code))];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        return (int) self::value($sql, $params, 0) > 0;
    }

    /** Génère un code aléatoire lisible et unique. */
    public static function generateCode(string $prefix = 'PROMO'): string
    {
        do {
            $code = strtoupper($prefix) . '-' . strtoupper(bin2hex(random_bytes(2))) . random_int(10, 99);
        } while (self::codeExists($code));

        return $code;
    }

    /**
     * Normalise les données d'un formulaire / payload.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function fromPayload(array $data): array
    {
        $productIds = $data['productIds'] ?? $data['product_ids'] ?? null;
        if (is_string($productIds)) {
            $decoded = json_decode($productIds, true);
            $productIds = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $productIds)));
        }
        $productIds = is_array($productIds)
            ? array_values(array_map('intval', array_filter($productIds, static fn ($v) => (int) $v > 0)))
            : [];

        $expiresAt = $data['expiresAt'] ?? $data['expires_at'] ?? null;
        if (is_string($expiresAt) && $expiresAt !== '') {
            $timestamp = strtotime($expiresAt);
            $expiresAt = $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
        } else {
            $expiresAt = null;
        }

        return [
            'percentage'          => (float) ($data['percentage'] ?? 0),
            'min_order_amount'    => isset($data['minOrderAmount']) && $data['minOrderAmount'] !== ''
                                        ? (float) $data['minOrderAmount'] : null,
            'max_discount_amount' => isset($data['maxDiscountAmount']) && $data['maxDiscountAmount'] !== ''
                                        ? (float) $data['maxDiscountAmount'] : null,
            'usage_limit'         => isset($data['usageLimit']) && $data['usageLimit'] !== '' ? (int) $data['usageLimit'] : null,
            'product_ids'         => $productIds,
            'user_id'             => isset($data['userId']) && $data['userId'] !== '' ? (int) $data['userId'] : null,
            'expires_at'          => $expiresAt,
            'is_active'           => (bool) ($data['isActive'] ?? $data['is_active'] ?? true),
        ];
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return strtotime((string) $this->expires_at) < time();
    }

    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && (int) $this->used_count >= (int) $this->usage_limit;
    }

    public function isGlobal(): bool
    {
        return $this->user_id === null;
    }

    /** Actif, non expiré, non épuisé et autorisé pour cet utilisateur. */
    public function isUsableBy(?int $userId = null): bool
    {
        if (!$this->is_active || $this->isExpired() || $this->isExhausted()) {
            return false;
        }
        if ($this->user_id !== null && (int) $this->user_id !== (int) $userId) {
            return false;
        }

        return true;
    }

    /** Le code s'applique-t-il aux produits présents dans le panier ? */
    public function appliesTo(array $productIds): bool
    {
        $restricted = is_array($this->product_ids) ? array_map('intval', $this->product_ids) : [];
        if ($restricted === []) {
            return true;
        }

        return array_intersect($restricted, array_map('intval', $productIds)) !== [];
    }

    /** Montant de la remise pour un sous-total donné. */
    public function computeDiscount(float $subtotal, array $productIds = []): float
    {
        if ($subtotal <= 0 || !$this->appliesTo($productIds)) {
            return 0.0;
        }
        if ($this->min_order_amount !== null && $subtotal < (float) $this->min_order_amount) {
            return 0.0;
        }

        $discount = $subtotal * ((float) $this->percentage / 100);
        if ($this->max_discount_amount !== null && $discount > (float) $this->max_discount_amount) {
            $discount = (float) $this->max_discount_amount;
        }

        return round(min($discount, $subtotal), 2);
    }

    public function incrementUsage(): void
    {
        self::db()->exec('UPDATE coupons SET used_count = used_count + 1 WHERE id = ' . (int) $this->id);
        $this->set('used_count', (int) $this->used_count + 1);
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Désactivé';
        }
        if ($this->isExpired()) {
            return 'Expiré';
        }
        if ($this->isExhausted()) {
            return 'Épuisé';
        }

        return 'Actif';
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'                  => (int) $this->id,
            'code'                => (string) $this->code,
            'percentage'          => (float) $this->percentage,
            'minOrderAmount'      => $this->min_order_amount,
            'maxDiscountAmount'   => $this->max_discount_amount,
            'usageLimit'          => $this->usage_limit === null ? null : (int) $this->usage_limit,
            'usedCount'           => (int) $this->used_count,
            'expiresAt'           => iso_date($this->expires_at),
            'isActive'            => (bool) $this->is_active,
            'isExpired'           => $this->isExpired(),
            'productIds'          => is_array($this->product_ids) ? array_map('intval', $this->product_ids) : null,
            'userId'              => $this->user_id === null ? null : (int) $this->user_id,
            'createdAt'           => iso_date($this->created_at),
            'updatedAt'           => iso_date($this->updated_at),
        ];
    }
}
