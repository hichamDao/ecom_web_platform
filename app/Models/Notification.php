<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Notification in-app (user_id NULL = message de diffusion).
 */
final class Notification extends Model
{
    protected static string $table = 'notifications';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['user_id', 'type', 'title', 'message', 'priority', 'is_read', 'data'];
    protected static array $casts = [
        'id'      => 'int',
        'user_id' => 'int',
        'is_read' => 'bool',
        'data'    => 'json',
    ];

    /** Types acceptés par l'application mobile (NotificationType côté Flutter). */
    public const TYPES = [
        'ORDER_RECEIVED', 'ORDER_CONFIRMED', 'ORDER_SHIPPED', 'ORDER_DELIVERED', 'ORDER_CANCELLED',
        'ORDER_REFUNDED', 'PAYMENT_SUCCESS', 'PAYMENT_FAILED', 'REFUND_PROCESSED', 'SHIPPING_UPDATE',
        'DELIVERY_ATTEMPT', 'PROMOTION', 'DISCOUNT', 'COUPON_CODE', 'FLASH_SALE', 'PASSWORD_CHANGED',
        'NEW_LOGIN', 'EMAIL_VERIFIED', 'REVIEW_APPROVED', 'REVIEW_REJECTED', 'SELLER_RESPONSE',
        'PRICE_DROP', 'BACK_IN_STOCK', 'LOW_STOCK', 'APP_UPDATE', 'MAINTENANCE', 'POLICY_CHANGE',
    ];

    /** @return array<int, self> */
    public static function forUser(int $userId, int $limit = 50): array
    {
        return self::rows(
            'SELECT * FROM notifications
             WHERE user_id = :userId OR user_id IS NULL
             ORDER BY created_at DESC LIMIT ' . (int) $limit,
            ['userId' => $userId]
        );
    }

    /** @return array<int, self> */
    public static function allRecent(int $limit = 100): array
    {
        return self::rows('SELECT * FROM notifications ORDER BY created_at DESC LIMIT ' . (int) $limit);
    }

    public static function unreadCount(int $userId): int
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM notifications WHERE is_read = 0 AND (user_id = :userId OR user_id IS NULL)',
            ['userId' => $userId],
            0
        );
    }

    public static function markAsRead(int $id, int $userId): void
    {
        self::db()->exec(
            'UPDATE notifications SET is_read = 1 WHERE id = ' . (int) $id
            . ' AND (user_id = ' . (int) $userId . ' OR user_id IS NULL)'
        );
    }

    public static function markAllAsRead(int $userId): void
    {
        self::db()->exec(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ' . (int) $userId
        );
    }

    public static function deleteFor(int $id, int $userId): void
    {
        self::db()->exec(
            'DELETE FROM notifications WHERE id = ' . (int) $id
            . ' AND (user_id = ' . (int) $userId . ' OR user_id IS NULL)'
        );
    }

    /** Crée une notification pour un utilisateur. */
    public static function notify(int $userId, string $type, string $title, string $message, string $priority = 'MEDIUM', array $data = []): self
    {
        return self::create([
            'user_id'  => $userId,
            'type'     => self::normalizeType($type),
            'title'    => $title,
            'message'  => $message,
            'priority' => self::normalizePriority($priority),
            'is_read'  => false,
            'data'     => $data === [] ? null : $data,
        ]);
    }

    /** Message diffusé à tous les utilisateurs (user_id NULL). */
    public static function broadcast(string $type, string $title, string $message, string $priority = 'MEDIUM', array $data = []): self
    {
        return self::create([
            'user_id'  => null,
            'type'     => self::normalizeType($type),
            'title'    => $title,
            'message'  => $message,
            'priority' => self::normalizePriority($priority),
            'is_read'  => false,
            'data'     => $data === [] ? null : $data,
        ]);
    }

    public static function normalizeType(string $type): string
    {
        $normalized = strtoupper(str_replace([' ', '-'], '_', trim($type)));
        // Flutter envoie "orderConfirmed" -> ORDERCONFIRMED : on accepte aussi cette forme
        $snake = strtoupper((string) preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $type));
        foreach (self::TYPES as $known) {
            if ($known === $normalized || $known === $snake || str_replace('_', '', $known) === str_replace('_', '', $normalized)) {
                return $known;
            }
        }

        return 'PROMOTION';
    }

    public static function normalizePriority(string $priority): string
    {
        $normalized = strtoupper(trim($priority));

        return in_array($normalized, ['HIGH', 'MEDIUM', 'LOW'], true) ? $normalized : 'MEDIUM';
    }

    public function isBroadcast(): bool
    {
        return $this->user_id === null;
    }

    public function priorityBadge(): string
    {
        return match (strtoupper((string) $this->priority)) {
            'HIGH'   => 'danger',
            'MEDIUM' => 'warning',
            default  => 'secondary',
        };
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'        => (int) $this->id,
            'type'      => (string) $this->type,
            'title'     => (string) $this->title,
            'message'   => (string) $this->message,
            'priority'  => strtoupper((string) $this->priority),
            'isRead'    => (bool) $this->is_read,
            'isBroadcast' => $this->isBroadcast(),
            'userId'    => $this->user_id === null ? null : (int) $this->user_id,
            'data'      => is_array($this->data) && $this->data !== [] ? $this->data : null,
            'createdAt' => iso_date($this->created_at),
        ];
    }
}
