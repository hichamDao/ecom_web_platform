<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use RuntimeException;

/**
 * Logique du panier : disponible pour l'API mobile et pour la boutique web.
 */
final class CartService
{
    public function cart(int $userId): Cart
    {
        return Cart::forUser($userId);
    }

    public function add(int $userId, int $productId, int $quantity = 1, ?string $size = null, ?string $color = null): Cart
    {
        $cart = $this->cart($userId);
        $this->assertStock($productId, $quantity, $size, $color);

        return $cart->addItem($productId, $quantity, $size, $color);
    }

    public function update(int $userId, int $itemId, int $quantity): Cart
    {
        return $this->cart($userId)->updateItem($itemId, max(1, $quantity));
    }

    public function remove(int $userId, int $itemId): Cart
    {
        return $this->cart($userId)->removeItem($itemId);
    }

    public function clear(int $userId): Cart
    {
        return $this->cart($userId)->clear();
    }

    /** Applique un code promo valide au panier. */
    public function applyCoupon(int $userId, string $code): Cart
    {
        $cart = $this->cart($userId);
        $coupon = Coupon::findByCode($code);

        if ($coupon === null) {
            throw new RuntimeException('Code promo introuvable : ' . $code);
        }
        if (!$coupon->isUsableBy($userId)) {
            throw new RuntimeException($this->reason($coupon));
        }
        if (!$coupon->appliesTo($cart->productIds())) {
            throw new RuntimeException('Ce code promo ne s\'applique pas aux produits de votre panier.');
        }
        if ($coupon->min_order_amount !== null && $cart->subtotal() < (float) $coupon->min_order_amount) {
            throw new RuntimeException(sprintf(
                'Commande minimum de %s requise pour ce code promo.',
                money((float) $coupon->min_order_amount)
            ));
        }

        $discount = $coupon->computeDiscount($cart->subtotal(), $cart->productIds());
        if ($discount <= 0) {
            throw new RuntimeException('Aucune remise applicable sur votre panier.');
        }

        return $cart->applyCoupon($coupon, $discount);
    }

    public function removeCoupon(int $userId): Cart
    {
        return $this->cart($userId)->removeCoupon();
    }

    /** @return array<string, mixed> */
    public function summary(Cart $cart): array
    {
        return [
            'subtotal'       => $cart->subtotal(),
            'shippingFee'    => $cart->shipping(),
            'discountAmount' => $cart->discount(),
            'total'          => $cart->total(),
            'totalItems'     => $cart->totalItems(),
        ];
    }

    private function assertStock(int $productId, int $quantity, ?string $size, ?string $color): void
    {
        $product = \App\Models\Product::find($productId);
        if ($product === null) {
            throw new RuntimeException('Produit introuvable.');
        }
        if (!$product->isInStock()) {
            throw new RuntimeException('Ce produit est en rupture de stock.');
        }
        if ((int) $product->stock < $quantity) {
            throw new RuntimeException(sprintf('Stock insuffisant : %d article(s) disponible(s).', (int) $product->stock));
        }
    }

    private function reason(Coupon $coupon): string
    {
        if (!$coupon->is_active) {
            return 'Ce code promo est désactivé.';
        }
        if ($coupon->isExpired()) {
            return 'Ce code promo est expiré.';
        }
        if ($coupon->isExhausted()) {
            return 'Ce code promo a atteint sa limite d\'utilisation.';
        }
        if (!$coupon->isGlobal()) {
            return 'Ce code promo est réservé à un autre client.';
        }

        return 'Ce code promo n\'est pas valide.';
    }
}
