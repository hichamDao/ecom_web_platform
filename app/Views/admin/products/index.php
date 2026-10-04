<?php
/**
 * Back-office : liste des produits.
 */
use App\Core\View;

$paginator = $paginator ?? null;
$filters = $filters ?? [];
$categories = $categories ?? [];
$products = $paginator === null ? [] : $paginator->items;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="get" class="d-flex gap-2">
        <?php if (!empty($filters['categoryId'])): ?>
            <input type="hidden" name="categorie" value="<?= (int) $filters['categoryId'] ?>">
        <?php endif; ?>
        <input type="search" name="q" class="form-control form-control-sm" style="width:240px"
               placeholder="Rechercher un produit" value="<?= e($filters['keyword'] ?? '') ?>">
        <select name="tri" class="form-select form-select-sm" style="width:auto" data-auto-submit>
            <option value="recent" <?= selected($filters['sort'] ?? 'recent', 'recent') ?>>Plus récents</option>
            <option value="price_asc" <?= selected($filters['sort'] ?? '', 'price_asc') ?>>Prix croissant</option>
            <option value="price_desc" <?= selected($filters['sort'] ?? '', 'price_desc') ?>>Prix décroissant</option>
            <option value="best" <?= selected($filters['sort'] ?? '', 'best') ?>>Meilleures ventes</option>
        </select>
        <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search"></i></button>
    </form>

    <a class="btn btn-sm btn-primary" href="<?= e(url('/admin/produits/nouveau')) ?>">
        <i class="bi bi-plus-lg me-1"></i>Nouveau produit
    </a>
</div>

<div class="table-card">
    <?php if ($products === []): ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam fs-1 text-muted-2"></i>
            <p class="mt-3 mb-3 text-muted-2">Aucun produit ne correspond à ces critères.</p>
            <a class="btn btn-primary btn-sm" href="<?= e(url('/admin/produits/nouveau')) ?>">Créer un produit</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th class="text-end">Prix</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Ventes</th>
                        <th class="text-center">Badges</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $product): ?>
                    <?php $category = $product->category(); ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e(img($product->thumbnail)) ?>" alt="" class="thumb-sm">
                                <div>
                                    <div class="fw-semibold"><?= e(excerpt($product->name, 40)) ?></div>
                                    <small class="text-muted-2">#<?= (int) $product->id ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="small"><?= e($category?->name ?? '—') ?></td>
                        <td class="text-end fw-semibold"><?= e(money($product->effectivePrice())) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= (int) $product->stock === 0 ? 'danger' : ((int) $product->stock <= 5 ? 'warning' : 'light text-dark') ?>">
                                <?= (int) $product->stock ?>
                            </span>
                        </td>
                        <td class="text-center small"><?= (int) $product->sold_count ?></td>
                        <td class="text-center">
                            <?php if ($product->is_featured): ?><span class="badge bg-primary">Vedette</span><?php endif; ?>
                            <?php if ($product->is_new): ?><span class="badge bg-info text-dark">Nouveau</span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= e(url('/admin/produits/' . (int) $product->id . '/modifier')) ?>"
                                   title="Modifier"><i class="bi bi-pencil"></i></a>
                                <a class="btn btn-sm btn-outline-secondary" href="<?= e(product_url($product)) ?>"
                                   target="_blank" title="Voir"><i class="bi bi-eye"></i></a>
                                <form method="post" action="<?= e(url('/admin/produits/' . (int) $product->id . '/supprimer')) ?>"
                                      onsubmit="return confirm('Supprimer ce produit ?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Supprimer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php View::partial('partials.pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>