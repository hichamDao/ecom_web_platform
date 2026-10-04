<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;
use RuntimeException;

/**
 * Panier d'un utilisateur (1 panier par utilisateur).
 */
final class Cart extends Model
{
    protected static string $table = 'carts';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['user_id', 'coupon_code', 'discount_amount'];
    protected static array $casts = ['id' => 'int', 'user_id' => 'int', 'discount_amount' => 'float'];

    /** @var array<int, CartItem> */
    public array $items = [];

    /** Récupère (ou crée) le panier de l'utilisateur avec ses articles chargés. */
    public static function forUser(int $userId): self
    {
        $cart = self::row('SELECT * FROM carts WHERE user_id = :userId LIMIT 1', ['userId' => $userId]);

        if ($cart === null) {
            $cart = self::create(['user_id' => $userId, 'discount_amount' => 0]);
        }

        return $cart->loadItems();
    }

    public function loadItems(): self
    {
        $rows = self::db()->query(
            'SELECT ci.* FROM cart_items ci
             INNER JOIN products p ON p.id = ci.product_id
             WHERE ci.cart_id = ' . (int) $this->id . '
             ORDER BY ci.id ASC'
        )->fetchAll();

        $this->items = [];
        foreach ($rows as $row) {
            $item = new CartItem();
            $item->hydrate($row);
            $this->items[] = $item;
        }

        // Met à jour les totaux à chaque chargement (le panier peut avoir été modifié via l'API mobile)
        $this->recalculate();

        return $this;
    }

    /** Recalcule le montant de la remise (le code promo peut avoir expiré). */
    public function recalculate(): self
    {
        $discount = 0.0;
        $code = $this->coupon_code;

        if (is_string($code) && $code !== '') {
            $coupon = Coupon::findByCode($code);
            $subtotal = $this->subtotal();
            $productIds = $this->productIds();

            if ($coupon === null || !$coupon->isUsableBy((int) $this->user_id) || !$coupon->appliesTo($productIds)) {
                $this->set('coupon_code', null);
                $this->set('discount_amount', 0.0);
                $this->save();

                return $this;
            }
            $discount = $coupon->computeDiscount($subtotal, $productIds);
        }

        $this->set('discount_amount', $discount);
        $this->save();

        return $this;
    }

    // -----------------------------------------------------------------
    // Totaux
    // -----------------------------------------------------------------

    public function totalItems(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += (int) $item->quantity;
        }

        return $total;
    }

    public function subtotal(): float
    {
        $subtotal = 0.0;
        foreach ($this->items as $item) {
            $subtotal += $item->unitPrice() * (int) $item->quantity;
        }

        return round($subtotal, 2);
    }

    public function discount(): float
    {
        return round((float) $this->discount_amount, 2);
    }

    public function shipping(): float
    {
        $subtotal = $this->subtotal();
        if ($this->items === [] || $subtotal >= free_shipping_from()) {
            return 0.0;
        }

        return round(shipping_fee(), 2);
    }

    public function total(): float
    {
        $total = $this->subtotal() + $this->shipping() - $this->discount();

        return round(max(0.0, $total), 2);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** @return array<int, int> */
    public function productIds(): array
    {
        return array_map(static fn (CartItem $item): int => (int) $item->product_id, $this->items);
    }

    // -----------------------------------------------------------------
    // Modification
    // -----------------------------------------------------------------

    public function addItem(int $productId, int $quantity = 1, ?string $size = null, ?string $color = null): self
    {
        $product = Product::find($productId);
        if ($product === null) {
            throw new RuntimeException('Produit introuvable.');
        }
        if (!$product->isInStock()) {
            throw new RuntimeException('Ce produit est en rupture de stock.');
        }

        $quantity = max(1, $quantity);

        $existing = self::row(
            'SELECT * FROM cart_items WHERE cart_id = :cartId AND product_id = :productId
             AND COALESCE(selected_size, \'\') = :size AND COALESCE(selected_color, \'\') = :color LIMIT 1',
            [
                'cartId'    => (int) $this->id,
                'productId' => $productId,
                'size'      => $size ?? '',
                'color'     => $color ?? '',
            ]
        );

        if ($existing !== null) {
            $newQuantity = (int) $existing->quantity + $quantity;
            $existing->quantity = min($newQuantity, max(1, (int) $product->stock));
            $existing->save();
        } else {
            CartItem::create([
                'cart_id'        => (int) $this->id,
                'product_id'     => $productId,
                'quantity'       => min($quantity, max(1, (int) $product->stock)),
                'selected_size'  => $size,
                'selected_color' => $color,
            ]);
        }

        return $this->loadItems();
    }

    public function updateItem(int $itemId, int $quantity): self
    {
        $item = self::row(
            'SELECT * FROM cart_items WHERE id = :id AND cart_id = :cartId LIMIT 1',
            ['id' => $itemId, 'cartId' => (int) $this->id]
        );

        if ($item !== null) {
            $quantity = max(1, $quantity);
            $product = $item->product();
            if ($product !== null) {
                $quantity = min($quantity, max(1, (int) $product->stock));
            }
            $item->quantity = $quantity;
            $item->save();
        }

        return $this->loadItems();
    }

    public function removeItem(int $itemId): self
    {
        CartItem::deleteWhere(['id' => $itemId, 'cart_id' => (int) $this->id]);

        return $this->loadItems();
    }

    public function clear(): self
    {
        CartItem::deleteWhere(['cart_id' => (int) $this->id]);
        $this->set('coupon_code', null);
        $this->set('discount_amount', 0.0);
        $this->save();

        return $this->loadItems();
    }

    public function applyCoupon(Coupon $coupon, float $discount): self
    {
        $this->set('coupon_code', (string) $coupon->code);
        $this->set('discount_amount', $discount);
        $this->save();

        return $this;
    }

    public function removeCoupon(): self
    {
        $this->set('coupon_code', null);
        $this->set('discount_amount', 0.0);
        $this->save();

        return $this;
    }

    /** Vide le panier après une commande. */
    public function emptyAfterOrder(): void
    {
        Database::instance()->transaction(function (): void {
            $this->clear();
        });
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'             => (int) $this->id,
            'userId'         => (int) $this->user_id,
            'items'          => array_map(static fn (CartItem $item): array => $item->toApi(), $this->items),
            'totalItems'     => $this->totalItems(),
            'subtotal'       => $this->subtotal(),
            'shippingFee'    => $this->shipping(),
            'discountAmount' => $this->discount(),
            'couponCode'     => $this->coupon_code,
            'total'          => $this->total(),
        ];
    }
}
