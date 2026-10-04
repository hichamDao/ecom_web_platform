<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Notification;

/**
 * /api/products — catalogue, avis produits (mobile + administration).
 */
final class ProductController extends ApiController
{
    /**
     * GET /api/products?page=0&size=10&keyword=&categoryId=&minPrice=&maxPrice=&sortBy=&sortDir=&featured=
     */
    public function index(): never
    {
        $page = max(0, $this->request->int('page', 0));
        $size = max(1, min(100, $this->request->int('size', (int) config('shop.default_page_size', 12))));

        $filters = [
            'keyword'   => $this->request->input('keyword'),
            'categoryId' => $this->request->input('categoryId'),
            'minPrice'  => $this->request->input('minPrice'),
            'maxPrice'  => $this->request->input('maxPrice'),
            'brand'     => $this->request->input('brand'),
            'sort'      => $this->sortKey(),
        ];

        $featured = $this->request->input('featured');
        if ($featured !== null && $featured !== '') {
            $filters['featured'] = (bool) $featured;
        }

        $paginator = Product::paginate($filters, $page, $size);
        $this->ok($paginator->map(static fn (Product $product): array => $product->toApi())->toArray());
    }

    /** GET /api/products/featured */
    public function featured(): never
    {
        $this->ok(array_map(
            static fn (Product $product): array => $product->toApi(),
            Product::featured($this->limit())
        ));
    }

    /** GET /api/products/new-arrivals */
    public function newArrivals(): never
    {
        $categoryId = $this->request->int('categoryId', 0);

        $this->ok(array_map(
            static fn (Product $product): array => $product->toApi(),
            Product::newArrivals($this->limit(), $categoryId > 0 ? $categoryId : null)
        ));
    }

    /** GET /api/products/best-sellers */
    public function bestSellers(): never
    {
        $this->ok(array_map(
            static fn (Product $product): array => $product->toApi(),
            Product::bestSellers($this->limit())
        ));
    }

    /** GET /api/products/{id} */
    public function show(int $id): never
    {
        $product = Product::find($id);
        if ($product === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $product->incrementViews();
        $this->ok($product->toApi());
    }

    /** GET /api/products/{id}/related */
    public function related(int $id): never
    {
        $product = Product::find($id);
        if ($product === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $this->ok(array_map(
            static fn (Product $item): array => $item->toApi(),
            Product::related($product, $this->limit())
        ));
    }

    /** GET /api/products/{id}/reviews */
    public function reviews(int $id): never
    {
        if (Product::find($id) === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $this->ok(array_map(
            static fn (Review $review): array => $review->toApi(),
            Review::forProduct($id)
        ));
    }

    /** POST /api/products/{id}/reviews */
    public function storeReview(int $id): never
    {
        $this->requireAuth();

        if (Product::find($id) === null) {
            $this->fail('Produit introuvable.', 404);
        }
        if (Review::userHasReviewed($id, (int) $this->userId())) {
            $this->fail('Vous avez déjà publié un avis sur ce produit.', 409);
        }

        $data = $this->validatePayload([
            'rating'  => 'required|numeric',
            'comment' => 'required|max:1000',
        ]);

        $rating = (float) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            $this->fail('La note doit être comprise entre 1 et 5.', 422);
        }

        $review = Review::create([
            'product_id'           => $id,
            'user_id'              => $this->userId(),
            'rating'               => $rating,
            'comment'              => trim((string) $data['comment']),
            'images'               => $this->arrayValue($this->request->input('images', [])),
            'status'               => Review::STATUS_PENDING,
            'is_verified_purchase' => Review::isVerifiedPurchase($id, (int) $this->userId()),
        ]);

        $userId = (int) $this->userId();
        $user = $this->user();
        $admins = \App\Models\User::rows("SELECT id FROM users WHERE role = 'ADMIN' AND is_active = 1");
        foreach ($admins as $admin) {
            Notification::notify(
                (int) $admin->id,
                'REVIEW_APPROVED',
                'Nouvel avis à modérer',
                sprintf('%s a déposé un avis (%s/5) sur « %s ».', $user?->fullName() ?? 'Un client', $rating, (string) Product::find($id)?->name),
                'MEDIUM',
                ['reviewId' => (int) $review->id]
            );
        }

        $this->created($review->toApi(), 'Avis envoyé, il sera publié après modération');
    }

    /** POST /api/products (admin) */
    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload([
            'name'        => 'required|max:200',
            'description' => 'max:5000',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
        ]);

        $payload = Product::fromPayload($data + $this->payload());
        $this->assertCategory($payload['category_id'] ?? null);

        $product = Product::create($payload);
        $this->created($product->toApi(), 'Produit créé');
    }

    /** PUT /api/products/{id} (admin) */
    public function update(int $id): never
    {
        $this->requireAdmin();

        $product = Product::find($id);
        if ($product === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $data = $this->validatePayload([
            'name'        => 'required|max:200',
            'description' => 'max:5000',
            'price'       => 'required|numeric',
            'stock'       => 'required|integer',
        ]);

        $payload = Product::fromPayload($data + $this->payload());
        $this->assertCategory($payload['category_id'] ?? null);

        foreach ($payload as $key => $value) {
            $product->{$key} = $value;
        }
        $product->save();

        $this->ok($product->toApi(), 'Produit mis à jour');
    }

    /** DELETE /api/products/{id} (admin) */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $product = Product::find($id);
        if ($product === null) {
            $this->fail('Produit introuvable.', 404);
        }

        $product->delete();
        $this->ok(null, 'Produit supprimé');
    }

    /** GET /api/products/meta/filters — marques et bornes de prix (formulaire de filtre). */
    public function filters(): never
    {
        $bounds = Product::rows('SELECT MIN(price) AS minPrice, MAX(price) AS maxPrice FROM products');
        $first = $bounds[0] ?? null;

        $this->ok([
            'brands'   => Product::brands(),
            'minPrice' => $first === null ? 0.0 : (float) $first->minPrice,
            'maxPrice' => $first === null ? 0.0 : (float) $first->maxPrice,
        ]);
    }

    // -----------------------------------------------------------------

    private function limit(): int
    {
        return max(1, min(50, $this->request->int('limit', 10)));
    }

    /** Traduit sortBy/sortDir (Flutter) en une clé de la liste blanche. */
    private function sortKey(): string
    {
        $sortBy = (string) $this->request->input('sortBy', '');
        $sortDir = strtolower((string) $this->request->input('sortDir', ''));

        if ($sortBy === '') {
            return 'recent';
        }

        $normalized = strtolower($sortBy);
        $map = [
            'price'         => $sortDir === 'desc' ? 'price_desc' : 'price_asc',
            'name'          => $sortDir === 'asc' ? 'name_asc' : 'name_desc',
            'createdat'     => 'recent',
            'created_at'    => 'recent',
            'soldcount'     => 'best',
            'sold_count'    => 'best',
            'rating'        => 'rating',
        ];

        return $map[$normalized] ?? 'recent';
    }

    private function assertCategory(mixed $categoryId): void
    {
        if ($categoryId === null || $categoryId === '') {
            return;
        }
        if (\App\Models\Category::find((int) $categoryId) === null) {
            $this->fail('Catégorie introuvable.', 422);
        }
    }
}