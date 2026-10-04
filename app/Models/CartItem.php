<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Ligne de panier.
 */
final class CartItem extends Model
{
    protected static string $table = 'cart_items';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['cart_id', 'product_id', 'quantity', 'selected_size', 'selected_color'];
    protected static array $casts = ['id' => 'int', 'cart_id' => 'int', 'product_id' => 'int', 'quantity' => 'int'];

    public function product(): ?Product
    {
        return Product::find((int) $this->product_id);
    }

    public function unitPrice(): float
    {
        $product = $this->product();

        return $product === null ? 0.0 : $product->effectivePrice();
    }

    public function totalPrice(): float
    {
        return round($this->unitPrice() * (int) $this->quantity, 2);
    }

    public function label(): string
    {
        $parts = [];
        if (!empty($this->selected_size)) {
            $parts[] = 'Taille ' . $this->selected_size;
        }
        if (!empty($this->selected_color)) {
            $parts[] = 'Couleur ' . $this->selected_color;
        }

        return $parts === [] ? '' : ' • ' . implode(' • ', $parts);
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        $product = $this->product();

        return [
            'id'            => (int) $this->id,
            'product'       => $product?->toApi(),
            'productId'     => (int) $this->product_id,
            'quantity'      => (int) $this->quantity,
            'selectedSize'  => $this->selected_size,
            'selectedColor' => $this->selected_color,
            'totalPrice'    => $this->totalPrice(),
        ];
    }
}
