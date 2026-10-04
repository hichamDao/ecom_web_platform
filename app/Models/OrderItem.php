<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Ligne de commande (instantané du produit au moment de l'achat).
 */
final class OrderItem extends Model
{
    protected static string $table = 'order_items';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'order_id', 'product_id', 'product_name', 'product_thumbnail',
        'quantity', 'unit_price', 'selected_size', 'selected_color',
    ];

    protected static array $casts = [
        'id'         => 'int',
        'order_id'   => 'int',
        'product_id' => 'int',
        'quantity'   => 'int',
        'unit_price' => 'float',
    ];

    public function totalPrice(): float
    {
        return round((float) $this->unit_price * (int) $this->quantity, 2);
    }

    /**
     * Produit associé. Si le produit a été supprimé, on reconstruit un
     * produit minimal à partir de l'instantané stocké dans la ligne de commande.
     *
     * @return array<string, mixed>
     */
    public function productPayload(): array
    {
        $product = $this->product_id === null ? null : Product::find((int) $this->product_id);
        if ($product !== null) {
            return $product->toApi();
        }

        return [
            'id'                  => (int) ($this->product_id ?? 0),
            'name'                => (string) $this->product_name,
            'description'         => '',
            'price'               => (float) $this->unit_price,
            'oldPrice'            => null,
            'discountPercentage'  => null,
            'stock'               => 0,
            'thumbnail'           => $this->product_thumbnail,
            'images'              => [],
            'rating'              => 0.0,
            'reviewCount'         => 0,
            'category'            => null,
            'brand'               => null,
            'isFeatured'          => false,
            'isNew'               => false,
            'attributes'          => null,
            'overlayImage'        => null,
        ];
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id'            => (int) $this->id,
            'product'       => $this->productPayload(),
            'quantity'      => (int) $this->quantity,
            'unitPrice'     => (float) $this->unit_price,
            'selectedSize'  => $this->selected_size,
            'selectedColor' => $this->selected_color,
            'totalPrice'    => $this->totalPrice(),
        ];
    }
}
