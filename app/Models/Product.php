<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;

/**
 * Produit du catalogue.
 */
final class Product extends Model
{
    protected static string $table = 'products';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'name', 'description', 'price', 'old_price', 'discount_percentage', 'stock',
        'thumbnail', 'images', 'attributes', 'brand', 'is_featured', 'is_new',
        'overlay_image', 'category_id',
    ];

    protected static array $casts = [
        'id'                  => 'int',
        'price'               => 'float',
        'old_price'           => 'float',
        'discount_percentage' => 'float',
        'stock'               => 'int',
        'is_featured'         => 'bool',
        'is_new'              => 'bool',
        'images'              => 'json',
        'attributes'          => 'json',
        'category_id'         => 'int',
        'sold_count'          => 'int',
        'views'               => 'int',
        'rating_cache'        => 'float',
        'review_count_cache'  => 'int',
    ];

    /** Triements autorisés (empêche l'injection SQL via ?sortBy=). */
    public const SORTS = [
        'recent'     => 'p.created_at DESC',
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc'   => 'p.name ASC',
        'name_desc'  => 'p.name DESC',
        'best'       => 'p.sold_count DESC',
        'rating'     => 'p.rating_cache DESC',
    ];

    /**
     * Recherche paginée avec filtres.
     *
     * @param array<string, mixed> $filters keyword, categoryId, minPrice, maxPrice,
     *                                    brand, featured, new, inStock, sort
     */
    public static function paginate(array $filters = [], int $page = 0, int $size = 12): Paginator
    {
        [$where, $params] = self::buildFilters($filters);

        $total = (int) self::value('SELECT COUNT(*) FROM products p' . $where, $params, 0);
        $offset = max(0, $page) * $size;

        $sort = self::SORTS[$filters['sort'] ?? 'recent'] ?? self::SORTS['recent'];

        $items = self::rows(
            'SELECT p.* FROM products p' . $where . ' ORDER BY ' . $sort . ' LIMIT ' . (int) $size . ' OFFSET ' . (int) $offset,
            $params
        );

        return new Paginator($items, $total, $page, $size);
    }

    /**
     * Construit la clause WHERE à partir des filtres.
     *
     * @param  array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function buildFilters(array $filters): array
    {
        $clauses = [];
        $params = [];

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $kw = '%' . $keyword . '%';
            $clauses[] = '(p.name LIKE :kw1 OR p.description LIKE :kw2 OR p.brand LIKE :kw3)';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
        }

        $categoryId = $filters['categoryId'] ?? null;
        if ($categoryId !== null && $categoryId !== '') {
            $category = Category::find((int) $categoryId);
            $ids = $category !== null ? $category->descendantIds() : [(int) $categoryId];
            $placeholders = [];
            foreach ($ids as $index => $id) {
                $placeholders[] = ':cat' . $index;
                $params['cat' . $index] = $id;
            }
            $clauses[] = 'p.category_id IN (' . implode(',', $placeholders) . ')';
        }

        if (isset($filters['minPrice']) && $filters['minPrice'] !== '' && is_numeric($filters['minPrice'])) {
            $clauses[] = 'p.price >= :minPrice';
            $params['minPrice'] = (float) $filters['minPrice'];
        }
        if (isset($filters['maxPrice']) && $filters['maxPrice'] !== '' && is_numeric($filters['maxPrice'])) {
            $clauses[] = 'p.price <= :maxPrice';
            $params['maxPrice'] = (float) $filters['maxPrice'];
        }
        if (!empty($filters['brand'])) {
            $clauses[] = 'p.brand = :brand';
            $params['brand'] = $filters['brand'];
        }
        if (array_key_exists('featured', $filters) && $filters['featured'] !== null && $filters['featured'] !== '') {
            $clauses[] = 'p.is_featured = :featured';
            $params['featured'] = (int) (bool) $filters['featured'];
        }
        if (array_key_exists('new', $filters) && $filters['new'] !== null && $filters['new'] !== '') {
            $clauses[] = 'p.is_new = :isNew';
            $params['isNew'] = (int) (bool) $filters['new'];
        }
        if (!empty($filters['inStock'])) {
            $clauses[] = 'p.stock > 0';
        }

        return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /** @return array<int, self> */
    public static function featured(int $limit = 8): array
    {
        return self::rows(
            'SELECT * FROM products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT ' . (int) $limit
        );
    }

    /** @return array<int, self> */
    public static function newArrivals(int $limit = 8, ?int $categoryId = null): array
    {
        $where = ' WHERE is_new = 1';
        $params = [];
        if ($categoryId !== null) {
            $where .= ' AND category_id = :categoryId';
            $params['categoryId'] = $categoryId;
        }

        return self::rows(
            'SELECT * FROM products' . $where . ' ORDER BY created_at DESC LIMIT ' . (int) $limit,
            $params
        );
    }

    /** @return array<int, self> */
    public static function bestSellers(int $limit = 8): array
    {
        return self::rows(
            'SELECT * FROM products ORDER BY sold_count DESC, created_at DESC LIMIT ' . (int) $limit
        );
    }

    /** @return array<int, self> Produits proches d'un produit donné. */
    public static function related(self $product, int $limit = 8): array
    {
        if ($product->category_id === null) {
            return self::newArrivals($limit);
        }

        return self::rows(
            'SELECT * FROM products WHERE category_id = :categoryId AND id <> :id
             ORDER BY sold_count DESC LIMIT ' . (int) $limit,
            ['categoryId' => (int) $product->category_id, 'id' => (int) $product->id]
        );
    }

    /** @return array<int, self> */
    public static function lowStock(int $threshold = 5, int $limit = 20): array
    {
        return self::rows(
            'SELECT * FROM products WHERE stock <= :threshold ORDER BY stock ASC LIMIT ' . (int) $limit,
            ['threshold' => $threshold]
        );
    }

    /** @return array<int, self> */
    public static function topSelling(int $limit = 5): array
    {
        return self::rows('SELECT * FROM products ORDER BY sold_count DESC LIMIT ' . (int) $limit);
    }

    /** @return array<int, string> */
    public static function brands(): array
    {
        $rows = self::db()
            ->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand")
            ->fetchAll();

        return array_map(static fn (array $row): string => (string) $row['brand'], $rows);
    }

    public function category(): ?Category
    {
        return $this->category_id === null ? null : Category::find((int) $this->category_id);
    }

    public function incrementViews(): void
    {
        $this->db()->exec('UPDATE products SET views = views + 1 WHERE id = ' . (int) $this->id);
        $this->set('views', (int) $this->views + 1);
    }

    public function isInStock(): bool
    {
        return (int) $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return (int) $this->stock > 0 && (int) $this->stock <= low_stock_threshold();
    }

    public function hasDiscount(): bool
    {
        return (float) $this->discount_percentage > 0
            || ($this->old_price !== null && (float) $this->old_price > (float) $this->price);
    }

    public function hasTryOn(): bool
    {
        return trim((string) $this->overlay_image) !== '';
    }

    public function effectivePrice(): float
    {
        return final_price($this);
    }

    /** @return array<int, string> */
    public function imageList(): array
    {
        $images = is_array($this->images) ? $this->images : [];
        $list = array_values(array_filter(array_map('strval', $images)));

        return $list === [] && !empty($this->thumbnail) ? [(string) $this->thumbnail] : $list;
    }

    /** Décrémente le stock et incrémente le compteur de ventes. */
    public function decreaseStock(int $quantity): void
    {
        $this->db()->exec(
            'UPDATE products SET stock = GREATEST(0, stock - ' . (int) $quantity . '),
             sold_count = sold_count + ' . (int) $quantity . ' WHERE id = ' . (int) $this->id
        );
    }

    public function increaseStock(int $quantity): void
    {
        $this->db()->exec(
            'UPDATE products SET stock = stock + ' . (int) $quantity . ',
             sold_count = GREATEST(0, sold_count - ' . (int) $quantity . ') WHERE id = ' . (int) $this->id
        );
    }

    public function refreshRating(): void
    {
        $row = self::db()->query(
            "SELECT ROUND(AVG(rating), 2) AS avg_rating, COUNT(*) AS total
             FROM reviews WHERE product_id = " . (int) $this->id . " AND status = 'APPROVED'"
        )->fetch() ?: [];

        self::db()->exec(
            'UPDATE products SET rating_cache = ' . (float) ($row['avg_rating'] ?? 0)
            . ', review_count_cache = ' . (int) ($row['total'] ?? 0)
            . ' WHERE id = ' . (int) $this->id
        );
    }

    /**
     * Normalise les données d'un formulaire / payload JSON.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function fromPayload(array $data): array
    {
        $payload = [
            'name'                 => trim((string) ($data['name'] ?? '')),
            'description'          => $data['description'] ?? null,
            'price'                => (float) ($data['price'] ?? 0),
            'old_price'            => isset($data['oldPrice']) && $data['oldPrice'] !== ''
                                        ? (float) $data['oldPrice'] : (isset($data['old_price']) ? (float) $data['old_price'] : null),
            'discount_percentage'  => isset($data['discountPercentage']) && $data['discountPercentage'] !== ''
                                        ? (float) $data['discountPercentage'] : null,
            'stock'                => (int) ($data['stock'] ?? 0),
            'thumbnail'            => $data['thumbnail'] ?? null,
            'brand'                => $data['brand'] ?? null,
            'is_featured'          => (bool) ($data['isFeatured'] ?? $data['is_featured'] ?? false),
            'is_new'               => (bool) ($data['isNew'] ?? $data['is_new'] ?? false),
            'overlay_image'        => $data['overlayImage'] ?? $data['overlay_image'] ?? null,
            'category_id'          => isset($data['categoryId']) && $data['categoryId'] !== ''
                                        ? (int) $data['categoryId']
                                        : (isset($data['category_id']) && $data['category_id'] !== ''
                                            ? (int) $data['category_id'] : null),
        ];

        $images = $data['images'] ?? null;
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : array_values(array_filter(preg_split('/[\r\n,]+/', $images) ?: []));
        }
        $payload['images'] = is_array($images) ? array_values(array_filter(array_map('trim', $images))) : [];

        $attributes = $data['attributes'] ?? null;
        if (is_string($attributes)) {
            $decoded = json_decode($attributes, true);
            $attributes = is_array($decoded) ? $decoded : null;
        }
        if (is_array($attributes)) {
            $payload['attributes'] = $attributes;
        } elseif (isset($data['sizes'], $data['colors'])) {
            $payload['attributes'] = array_filter([
                'tailles'  => $data['sizes'] !== '' ? (array) $data['sizes'] : null,
                'couleurs' => $data['colors'] !== '' ? (array) $data['colors'] : null,
            ]);
        }

        // Recalcule le pourcentage de remise à partir de l'ancien prix
        $price = (float) $payload['price'];
        $oldPrice = $payload['old_price'] !== null ? (float) $payload['old_price'] : null;
        if ($oldPrice !== null && $oldPrice > $price && $price > 0) {
            $payload['discount_percentage'] = round((($oldPrice - $price) / $oldPrice) * 100, 2);
        } elseif ($oldPrice !== null && $oldPrice <= $price) {
            $payload['old_price'] = null;
        }

        return $payload;
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        $category = $this->category();

        return [
            'id'                  => (int) $this->id,
            'name'                => (string) $this->name,
            'description'         => (string) $this->description,
            'price'               => (float) $this->price,
            'oldPrice'            => $this->old_price === null ? null : (float) $this->old_price,
            'discountPercentage'  => $this->discount_percentage === null ? null : (float) $this->discount_percentage,
            'stock'               => (int) $this->stock,
            'thumbnail'           => $this->thumbnail,
            'images'              => $this->imageList(),
            'rating'              => (float) $this->rating_cache,
            'reviewCount'         => (int) $this->review_count_cache,
            'category'            => $category?->toApi(),
            'categoryId'          => $this->category_id === null ? null : (int) $this->category_id,
            'brand'               => $this->brand,
            'isFeatured'          => (bool) $this->is_featured,
            'isNew'               => (bool) $this->is_new,
            'attributes'          => is_array($attributes = $this->get('attributes')) && $attributes !== [] ? $attributes : null,
            'overlayImage'        => $this->overlay_image,
            'soldCount'           => (int) $this->sold_count,
            'views'               => (int) $this->views,
            'createdAt'           => iso_date($this->created_at),
        ];
    }
}
