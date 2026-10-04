<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

/**
 * Avis client sur un produit (modération : PENDING / APPROVED / REJECTED).
 */
final class Review extends Model
{
    protected static string $table = 'reviews';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'product_id', 'user_id', 'rating', 'comment', 'images', 'status', 'is_verified_purchase',
    ];

    protected static array $casts = [
        'id'                   => 'int',
        'product_id'           => 'int',
        'user_id'              => 'int',
        'rating'               => 'float',
        'images'               => 'json',
        'is_verified_purchase' => 'bool',
    ];

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';

    /** @return array<int, self> */
    public static function forProduct(int $productId, bool $onlyApproved = true, int $limit = 50): array
    {
        $where = $onlyApproved ? " AND status = 'APPROVED'" : '';

        return self::rows(
            'SELECT r.*, u.username, u.first_name, u.last_name, u.avatar
             FROM reviews r
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.product_id = :productId' . $where . '
             ORDER BY r.created_at DESC LIMIT ' . (int) $limit,
            ['productId' => $productId]
        );
    }

    /** @return array<int, self> */
    public static function forUser(int $userId): array
    {
        return self::rows(
            'SELECT r.*, p.name AS product_name, p.thumbnail AS product_thumbnail
             FROM reviews r
             LEFT JOIN products p ON p.id = r.product_id
             WHERE r.user_id = :userId
             ORDER BY r.created_at DESC',
            ['userId' => $userId]
        );
    }

    /** @return array<int, self> */
    public static function pending(int $limit = 100): array
    {
        return self::rows(
            "SELECT r.*, p.name AS product_name, p.thumbnail AS product_thumbnail,
                    u.username, u.first_name, u.last_name
             FROM reviews r
             LEFT JOIN products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id
             WHERE r.status = 'PENDING'
             ORDER BY r.created_at ASC LIMIT " . (int) $limit
        );
    }

    public static function paginateReviews(string $status = '', int $page = 0, int $size = 20): Paginator
    {
        $where = '';
        $params = [];
        if ($status !== '') {
            $where = ' WHERE r.status = :status';
            $params['status'] = strtoupper($status);
        }

        $total = (int) self::value(
            'SELECT COUNT(*) FROM reviews r' . $where,
            $params,
            0
        );

        $items = self::rows(
            'SELECT r.*, p.name AS product_name, p.thumbnail AS product_thumbnail,
                    u.username, u.first_name, u.last_name, u.avatar
             FROM reviews r
             LEFT JOIN products p ON p.id = r.product_id
             LEFT JOIN users u ON u.id = r.user_id' . $where . '
             ORDER BY r.created_at DESC LIMIT ' . (int) $size . ' OFFSET ' . (int) ($page * $size),
            $params
        );

        return new Paginator($items, $total, $page, $size);
    }

    public static function countByStatus(string $status): int
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM reviews WHERE status = :status',
            ['status' => strtoupper($status)],
            0
        );
    }

    public static function userHasReviewed(int $productId, int $userId): bool
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM reviews WHERE product_id = :productId AND user_id = :userId',
            ['productId' => $productId, 'userId' => $userId],
            0
        ) > 0;
    }

    /** Le client a-t-il déjà acheté le produit (avis vérifié) ? */
    public static function isVerifiedPurchase(int $productId, int $userId): bool
    {
        return (int) self::value(
            "SELECT COUNT(*) FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE oi.product_id = :productId AND o.user_id = :userId AND o.is_paid = 1
               AND o.status IN ('SHIPPED', 'DELIVERED', 'PROCESSING', 'CONFIRMED')",
            ['productId' => $productId, 'userId' => $userId],
            0
        ) > 0;
    }

    public function product(): ?Product
    {
        return $this->product_id === null ? null : Product::find((int) $this->product_id);
    }

    public function author(): ?User
    {
        return $this->user_id === null ? null : User::find((int) $this->user_id);
    }

    public function approve(): void
    {
        $this->set('status', self::STATUS_APPROVED);
        $this->save();
        $this->refreshProduct();
    }

    public function reject(): void
    {
        $this->set('status', self::STATUS_REJECTED);
        $this->save();
        $this->refreshProduct();
    }

    public function delete(): bool
    {
        $productId = (int) $this->product_id;
        $deleted = parent::delete();
        if ($deleted) {
            self::refreshProductCaches($productId);
        }

        return $deleted;
    }

    private function refreshProduct(): void
    {
        $product = $this->product();
        $product?->refreshRating();
    }

    public static function refreshProductCaches(int $productId): void
    {
        $row = self::db()->query(
            "SELECT ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS total
             FROM reviews WHERE product_id = {$productId} AND status = 'APPROVED'"
        )->fetch() ?: [];

        self::db()->exec(
            "UPDATE products SET rating_cache = " . (float) ($row['avg_rating'] ?? 0)
            . ', review_count_cache = ' . (int) ($row['total'] ?? 0)
            . " WHERE id = {$productId}"
        );
    }

    public function authorName(): string
    {
        $first = trim((string) ($this->first_name ?? ''));
        $last = trim((string) ($this->last_name ?? ''));
        $name = trim($first . ' ' . $last);

        return $name !== '' ? $name : (string) ($this->username ?? 'Client');
    }

    public function statusLabel(): string
    {
        return match (strtoupper((string) $this->status)) {
            self::STATUS_APPROVED => 'Publié',
            self::STATUS_REJECTED => 'Rejeté',
            default              => 'En attente',
        };
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        $user = $this->author();

        return [
            'id'                 => (int) $this->id,
            'productId'          => $this->product_id === null ? null : (int) $this->product_id,
            'userId'             => $this->user_id === null ? null : (int) $this->user_id,
            'user'               => $user?->toApi(),
            'userName'           => $this->authorName(),
            'userAvatar'         => $this->avatar,
            'rating'             => (float) $this->rating,
            'comment'            => (string) $this->comment,
            'images'             => is_array($this->images) ? array_values($this->images) : [],
            'isVerifiedPurchase' => (bool) $this->is_verified_purchase,
            'status'             => $this->status,
            'createdAt'          => iso_date($this->created_at),
        ];
    }
}
