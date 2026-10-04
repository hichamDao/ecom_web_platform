<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Product;
use App\Models\Wishlist;

/**
 * /api/wishlist — liste de favoris.
 */
final class WishlistController extends ApiController
{
    /** GET /api/wishlist */
    public function index(): never
    {
        $this->requireAuth();

        $products = Wishlist::products((int) $this->userId());
        $this->ok(array_map(static fn (Product $product): array => $product->toApi(), $products));
    }

    /** POST /api/wishlist/{productId} */
    public function store(int $productId): never
    {
        $this->requireAuth();

        if (Product::find($productId) === null) {
            $this->fail('Produit introuvable.', 404);
        }

        Wishlist::add((int) $this->userId(), $productId);
        $this->created(['productId' => $productId, 'inWishlist' => true], 'Ajouté aux favoris');
    }

    /** DELETE /api/wishlist/{productId} */
    public function destroy(int $productId): never
    {
        $this->requireAuth();

        Wishlist::remove((int) $this->userId(), $productId);
        $this->ok(['productId' => $productId, 'inWishlist' => false], 'Retiré des favoris');
    }

    /** GET /api/wishlist/{productId}/check */
    public function check(int $productId): never
    {
        $this->requireAuth();

        $this->ok([
            'productId'  => $productId,
            'inWishlist' => Wishlist::contains((int) $this->userId(), $productId),
            'count'      => Wishlist::countFor((int) $this->userId()),
        ]);
    }

    /** POST /api/wishlist/{productId}/toggle */
    public function toggle(int $productId): never
    {
        $this->requireAuth();

        if (Product::find($productId) === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $inWishlist = Wishlist::toggle((int) $this->userId(), $productId);
        $this->ok(['productId' => $productId, 'inWishlist' => $inWishlist]);
    }
}