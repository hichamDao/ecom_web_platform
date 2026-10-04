<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Upload;
use App\Models\Category;
use App\Models\Product;
use Throwable;

/**
 * Back-office : gestion du catalogue produits.
 */
final class ProductController extends Controller
{
    public function index(): never
    {
        $this->requireAdmin();

        $keyword = trim((string) $this->request->input('q', ''));
        $page = max(1, $this->request->int('page', 1));

        $filters = [
            'keyword'    => $keyword,
            'categoryId' => $this->request->int('categorie', 0) ?: null,
            'inStock'    => $this->request->bool('rupture', false),
            'sort'       => (string) $this->request->input('tri', 'recent'),
        ];

        $paginator = Product::paginate(array_filter($filters, static fn ($v): bool => $v !== null && $v !== ''), $page - 1, 20);
        $paginator->page = $page - 1;

        $this->view('admin/products/index', [
            'title'     => 'Produits',
            'paginator' => $paginator,
            'filters'   => $filters,
            'categories' => Category::allOrdered(),
        ], 'layouts/admin');
    }

    public function create(): never
    {
        $this->requireAdmin();

        $this->view('admin/products/form', [
            'title'      => 'Nouveau produit',
            'product'    => null,
            'categories' => Category::allOrdered(),
            'action'     => url('/admin/produits'),
        ], 'layouts/admin');
    }

    public function store(): never
    {
        $this->requireAdmin();

        $data = $this->validate([
            'name'  => 'required|max:200',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ], '/admin/produits/nouveau');

        $payload = Product::fromPayload($data + $this->request->all());

        try {
            $payload['thumbnail'] = Upload::image($this->request->file('image'), 'products') ?? $payload['thumbnail'];
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/admin/produits/nouveau');
        }

        $product = Product::create($payload);

        $this->success('Produit « ' . $product->name . ' » créé.', '/admin/produits');
    }

    public function edit(int $id): never
    {
        $this->requireAdmin();

        $product = Product::find($id);
        if ($product === null) {
            $this->abort('Produit introuvable', 404);
        }

        $this->view('admin/products/form', [
            'title'      => 'Modifier le produit',
            'product'    => $product,
            'categories' => Category::allOrdered(),
            'action'     => url('/admin/produits/' . $id),
        ], 'layouts/admin');
    }

    public function update(int $id): never
    {
        $this->requireAdmin();

        $product = Product::find($id);
        if ($product === null) {
            $this->abort('Produit introuvable', 404);
        }

        $data = $this->validate([
            'name'  => 'required|max:200',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ], '/admin/produits/' . $id . '/modifier');

        $payload = Product::fromPayload($data + $this->request->all());

        try {
            $uploaded = Upload::image($this->request->file('image'), 'products');
            if ($uploaded !== null) {
                Upload::delete($product->thumbnail);
                $payload['thumbnail'] = $uploaded;
            }
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/admin/produits/' . $id . '/modifier');
        }

        foreach ($payload as $key => $value) {
            $product->{$key} = $value;
        }
        $product->save();

        $this->success('Produit mis à jour.', '/admin/produits');
    }

    public function destroy(int $id): never
    {
        $this->requireAdmin();

        $product = Product::find($id);
        if ($product === null) {
            $this->abort('Produit introuvable', 404);
        }

        Upload::delete($product->thumbnail);
        $product->delete();

        $this->success('Produit supprimé.', '/admin/produits');
    }
}