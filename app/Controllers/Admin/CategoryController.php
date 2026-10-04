<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Category;
use App\Models\Product;

/**
 * Back-office : arborescence des catégories.
 */
final class CategoryController extends Controller
{
    public function index(): never
    {
        $this->requireAdmin();

        $categories = Category::allOrdered();
        $products = Product::rows('SELECT id, category_id, name FROM products');
        $counts = [];
        foreach ($products as $product) {
            if ($product->category_id === null) {
                continue;
            }
            $counts[(int) $product->category_id] = ($counts[(int) $product->category_id] ?? 0) + 1;
        }

        $this->view('admin/categories/index', [
            'title'      => 'Catégories',
            'categories' => $categories,
            'counts'     => $counts,
        ], 'layouts/admin');
    }

    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validate(['name' => 'required|max:120'], '/admin/categories');
        $parentId = $this->request->int('parent_id', 0);

        Category::create([
            'name'        => trim((string) $data['name']),
            'description' => $this->request->input('description'),
            'image'       => $this->request->input('image'),
            'parent_id'   => $parentId > 0 ? $parentId : null,
        ]);

        $this->success('Catégorie créée.', '/admin/categories');
    }

    public function update(int $id): never
    {
        $this->requireAdmin();

        $category = Category::find($id);
        if ($category === null) {
            $this->abort('Catégorie introuvable', 404);
        }

        $data = $this->validate(['name' => 'required|max:120'], '/admin/categories');
        $parentId = $this->request->int('parent_id', 0);

        if ($parentId === $id) {
            $this->failure('Une catégorie ne peut pas être son propre parent.', '/admin/categories');
        }

        $category->name = trim((string) $data['name']);
        $category->description = $this->request->input('description', $category->description);
        $category->image = $this->request->input('image', $category->image);
        $category->parent_id = $parentId > 0 ? $parentId : null;
        $category->save();

        $this->success('Catégorie mise à jour.', '/admin/categories');
    }

    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $category = Category::find($id);
        if ($category === null) {
            $this->abort('Catégorie introuvable', 404);
        }
        if ($category->hasChildren()) {
            $this->failure('Supprimez d\'abord les sous-catégories.', '/admin/categories');
        }
        if ($category->hasProducts()) {
            $this->failure('Cette catégorie contient encore des produits.', '/admin/categories');
        }

        $category->delete();
        $this->success('Catégorie supprimée.', '/admin/categories');
    }
}