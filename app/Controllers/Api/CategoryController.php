<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Category;

/**
 * /api/categories — arborescence du catalogue.
 */
final class CategoryController extends ApiController
{
    /** GET /api/categories */
    public function index(): never
    {
        $withChildren = $this->request->bool('tree', true);
        $categories = Category::allOrdered();

        $this->ok(array_map(
            static fn (Category $category): array => $category->toApi($withChildren),
            $categories
        ));
    }

    /** GET /api/categories/{id} */
    public function show(int $id): never
    {
        $category = Category::find($id);
        if ($category === null) {
            $this->fail('Catégorie introuvable.', 404);
        }

        $this->ok($category->toApi(true));
    }

    /** POST /api/categories (admin) */
    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload([
            'name' => 'required|max:120',
            'description' => 'max:2000',
        ]);

        $parentId = $this->request->input('parentId', $this->request->input('parent_id'));
        if ($parentId !== null && $parentId !== '' && Category::find((int) $parentId) === null) {
            $this->fail('Catégorie parente introuvable.', 422);
        }

        $category = Category::create([
            'name'        => trim((string) $data['name']),
            'description' => $this->request->input('description'),
            'image'       => $this->request->input('image'),
            'parent_id'   => $parentId !== null && $parentId !== '' ? (int) $parentId : null,
        ]);

        $this->created($category->toApi(true), 'Catégorie créée');
    }

    /** PUT /api/categories/{id} (admin) */
    public function update(int $id): never
    {
        $this->requireAdmin();

        $category = Category::find($id);
        if ($category === null) {
            $this->fail('Catégorie introuvable.', 404);
        }

        $data = $this->validatePayload(['name' => 'required|max:120']);

        $parentId = $this->request->input('parentId', $category->parent_id);
        if ($parentId !== null && $parentId !== '' && (int) $parentId === $id) {
            $this->fail('Une catégorie ne peut pas être son propre parent.', 422);
        }
        if ($parentId !== null && $parentId !== '' && Category::find((int) $parentId) === null) {
            $this->fail('Catégorie parente introuvable.', 422);
        }

        $category->name = trim((string) $data['name']);
        $category->description = $this->request->input('description', $category->description);
        $category->image = $this->request->input('image', $category->image);
        $category->parent_id = $parentId !== null && $parentId !== '' ? (int) $parentId : null;
        $category->save();

        $this->ok($category->toApi(true), 'Catégorie mise à jour');
    }

    /** DELETE /api/categories/{id} (admin) */
    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $category = Category::find($id);
        if ($category === null) {
            $this->fail('Catégorie introuvable.', 404);
        }
        if ($category->hasChildren()) {
            $this->fail('Supprimez d\'abord les sous-catégories de cette catégorie.', 409);
        }

        $category->delete();
        $this->ok(null, 'Catégorie supprimée');
    }
}