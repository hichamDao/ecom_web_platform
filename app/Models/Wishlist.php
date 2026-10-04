<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Liste de favoris d'un utilisateur.
 */
final class Wishlist extends Model
{
    protected static string $table = 'wishlists';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['user_id', 'product_id'];
    protected static array $casts = ['id' => 'int', 'user_id' => 'int', 'product_id' => 'int'];

    /** @return array<int, Product> */
    public static function products(int $userId): array
    {
        $rows = self::db()->query(
            'SELECT p.* FROM products p
             INNER JOIN wishlists w ON w.product_id = p.id
             WHERE w.user_id = ' . (int) $userId . '
             ORDER BY w.created_at DESC'
        )->fetchAll();

        $products = [];
        foreach ($rows as $row) {
            $product = new Product();
            $product->hydrate($row);
            $products[] = $product;
        }

        return $products;
    }

    /** Le produit figure-t-il déjà dans les favoris ? (renommé car Model déclare has()) */
public static function contains(int $userId, int $productId): bool
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM wishlists WHERE user_id = :userId AND product_id = :productId',
            ['userId' => $userId, 'productId' => $productId],
            0
        ) > 0;
    }

    public static function add(int $userId, int $productId): void
    {
        if (self::contains($userId, $productId)) {
            return;
        }

        self::create(['user_id' => $userId, 'product_id' => $productId]);
    }

    public static function remove(int $userId, int $productId): void
    {
        self::deleteWhere(['user_id' => $userId, 'product_id' => $productId]);
    }

    public static function toggle(int $userId, int $productId): bool
    {
        if (self::contains($userId, $productId)) {
            self::remove($userId, $productId);

            return false;
        }

        self::add($userId, $productId);

        return true;
    }

    public static function countFor(int $userId): int
    {
        return (int) self::value('SELECT COUNT(*) FROM wishlists WHERE user_id = :id', ['id' => $userId], 0);
    }

    /** @return array<int, int> */
    public static function productIds(int $userId): array
    {
        $rows = self::db()
            ->query('SELECT product_id FROM wishlists WHERE user_id = ' . (int) $userId)
            ->fetchAll();

        return array_map(static fn (array $row): int => (int) $row['product_id'], $rows);
    }
}
