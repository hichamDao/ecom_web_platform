<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Catégorie de produits, hiérarchique (parent_id).
 */
final class Category extends Model
{
    protected static string $table = 'categories';
    protected static string $primaryKey = 'id';

    protected static array $fillable = ['name', 'description', 'image', 'parent_id'];
    protected static array $casts = ['id' => 'int', 'parent_id' => 'int'];

    /** @return array<int, self> */
    public static function allOrdered(): array
    {
        return self::rows('SELECT * FROM categories ORDER BY name ASC');
    }

    /** @return array<int, self> */
    public static function roots(): array
    {
        return self::rows('SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name ASC');
    }

    /** @return array<int, self> */
    public static function children(int $parentId): array
    {
        return self::rows('SELECT * FROM categories WHERE parent_id = :id ORDER BY name ASC', ['id' => $parentId]);
    }

    /**
     * Catégories racines enrichies de leurs sous-catégories et du nombre de produits.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function tree(): array
    {
        $counts = self::productCounts();
        $tree = [];
        foreach (self::allOrdered() as $category) {
            $node = [
                'id'            => (int) $category->id,
                'name'          => (string) $category->name,
                'description'   => $category->description,
                'image'         => $category->image,
                'parent_id'     => $category->parent_id,
                'product_count' => (int) ($counts[(int) $category->id] ?? 0),
                'children'      => [],
            ];
            if ($category->parent_id === null) {
                $tree[] = $node;
            } else {
                foreach ($tree as $index => $parent) {
                    if ($parent['id'] === (int) $category->parent_id) {
                        $tree[$index]['children'][] = $node;
                    }
                }
            }
        }

        return $tree;
    }

    /** @return array<int, int> */
    public static function productCounts(): array
    {
        $rows = self::db()->query('SELECT category_id, COUNT(*) AS total FROM products GROUP BY category_id')->fetchAll();
        $counts = [];
        foreach ($rows as $row) {
            if ($row['category_id'] !== null) {
                $counts[(int) $row['category_id']] = (int) $row['total'];
            }
        }

        return $counts;
    }

    public function productCount(): int
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM products WHERE category_id = :id',
            ['id' => (int) $this->id],
            0
        );
    }

    public function hasChildren(): bool
    {
        return (int) self::value(
            'SELECT COUNT(*) FROM categories WHERE parent_id = :id',
            ['id' => (int) $this->id],
            0
        ) > 0;
    }

    public function hasProducts(): bool
    {
        return $this->productCount() > 0;
    }

    /**
     * Identifiants de la catégorie et de toutes ses descendantes.
     *
     * @return array<int, int>
     */
    public function descendantIds(): array
    {
        $ids = [(int) $this->id];
        $frontier = $ids;
        $guard = 0;

        while ($frontier !== [] && $guard < 10) {
            $placeholders = implode(',', array_fill(0, count($frontier), '?'));
            $statement = self::db()->prepare(
                'SELECT id FROM categories WHERE parent_id IN (' . $placeholders . ')'
            );
            $statement->execute($frontier);
            $rows = $statement->fetchAll();
            $frontier = [];
            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if (!in_array($id, $ids, true)) {
                    $ids[] = $id;
                    $frontier[] = $id;
                }
            }
            $guard++;
        }

        return $ids;
    }

    public function path(string $separator = ' / '): string
    {
        $names = [(string) $this->name];
        $parentId = $this->parent_id;
        $guard = 0;
        while ($parentId !== null && $guard < 5) {
            $parent = self::find((int) $parentId);
            if ($parent === null) {
                break;
            }
            array_unshift($names, (string) $parent->name);
            $parentId = $parent->parent_id;
            $guard++;
        }

        return implode($separator, $names);
    }

    /** @return array<string, mixed> */
    public function toApi(bool $withChildren = false): array
    {
        $payload = [
            'id'           => (int) $this->id,
            'name'         => (string) $this->name,
            'description'  => $this->description,
            'image'        => $this->image,
            'productCount' => $this->productCount(),
            'parentId'     => $this->parent_id === null ? null : (int) $this->parent_id,
        ];

        if ($withChildren) {
            $payload['subCategories'] = array_map(
                static fn (self $child): array => $child->toApi(),
                self::children((int) $this->id)
            );
        }

        return $payload;
    }
}
