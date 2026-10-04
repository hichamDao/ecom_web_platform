<?php
/**
 * Catalogue : filtres + grille de produits + pagination.
 */
use App\Core\View;

$paginator = $paginator ?? null;
$filters = $filters ?? [];
$brands = $brands ?? [];
$sorts = $sorts ?? [];
$category = $category ?? null;
$wishlistIds = $wishlistIds ?? [];
$products = $paginator === null ? [] : $paginator->items;

$currentSort = (string) ($filters['sort'] ?? 'recent');
?>

<nav aria-label="fil d'ariane">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Accueil</a></li>
        <?php if ($category !== null): ?>
            <li class="breadcrumb-item"><a href="<?= e(url('/boutique')) ?>">Boutique</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($category->name) ?></li>
        <?php else: ?>
            <li class="breadcrumb-item active" aria-current="page">Boutique</li>
        <?php endif; ?>
    </ol>
</nav>

<div class="shop-header">
    <div>
        <h1 class="shop-title">
            <?= $category !== null ? e($category->path()) : 'Tous nos produits' ?>
        </h1>
        <p class="shop-count"><?= (int) ($paginator?->total ?? 0) ?> produit(s) disponible(s)</p>
    </div>

    <form method="get" class="shop-sort">
        <?php foreach ($filters as $key => $value): ?>
            <?php if (in_array($key, ['sort'], true) || $value === '' || $value === null) continue; ?>
            <?php if ($key === 'categoryId') continue; ?>
            <input type="hidden" name="<?= e($key === 'keyword' ? 'keyword' : ($key === 'minPrice' ? 'min' : ($key === 'maxPrice' ? 'max' : $key))) ?>"
                   value="<?= e(is_scalar($value) ? $value : '') ?>">
        <?php endforeach; ?>
        <label for="tri">Trier par</label>
        <select class="form-select form-select-sm" name="tri" id="tri" data-auto-submit>
            <?php foreach ($sorts as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= selected($currentSort, $key) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="shop-layout">
    <aside class="shop-sidebar">
        <div class="panel">
            <h2 class="panel-title">Filtres</h2>
            <form method="get" class="vstack gap-3">
                <div>
                    <label class="form-label small fw-semibold" for="keyword">Recherche</label>
                    <input type="search" class="form-control form-control-sm" id="keyword" name="keyword"
                           value="<?= e($filters['keyword'] ?? '') ?>" placeholder="Nom, marque...">
                </div>

                <div>
                    <label class="form-label small fw-semibold" for="min">Prix minimum</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="min" name="min"
                           value="<?= e($filters['minPrice'] ?? '') ?>">
                </div>
                <div>
                    <label class="form-label small fw-semibold" for="max">Prix maximum</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="max" name="max"
                           value="<?= e($filters['maxPrice'] ?? '') ?>">
                </div>

                <div>
                    <label class="form-label small fw-semibold" for="marque">Marque</label>
                    <select class="form-select form-select-sm" id="marque" name="marque">
                        <option value="">Toutes les marques</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= e($brand) ?>" <?= selected($filters['brand'] ?? '', $brand) ?>><?= e($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="disponible" name="disponible" value="1"
                        <?= !empty($filters['inStock']) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="disponible">En stock uniquement</label>
                </div>

                <input type="hidden" name="tri" value="<?= e($currentSort) ?>">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-grow-1" type="submit">Filtrer</button>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/boutique')) ?>">Réinitialiser</a>
                </div>
            </form>
        </div>

        <div class="panel">
            <h2 class="panel-title">Catégories</h2>
            <ul class="list-unstyled small mb-0">
                <?php foreach (($categories ?? []) as $node): ?>
                    <li class="mb-2">
                        <a href="<?= e(url('/categorie/' . (int) $node['id'])) ?>"
                           class="category-link">
                            <span><?= e($node['name']) ?></span>
                            <span class="category-count"><?= (int) $node['product_count'] ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>

    <section class="shop-products">
        <?php if ($products === []): ?>
            <div class="panel text-center py-5">
                <i class="bi bi-search fs-1 text-muted-2"></i>
                <h2 class="h5 mt-3">Aucun produit ne correspond à votre recherche</h2>
                <p class="text-muted-2">Essayez d'élargir vos critères de filtrage.</p>
                <a class="btn btn-outline-primary" href="<?= e(url('/boutique')) ?>">Voir tout le catalogue</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php View::partial('partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds]); ?>
                <?php endforeach; ?>
            </div>

            <?php View::partial('partials.pagination', ['paginator' => $paginator]); ?>
        <?php endif; ?>
    </section>
</div>