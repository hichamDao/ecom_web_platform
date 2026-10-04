<?php
/**
 * Carte produit réutilisée partout dans la boutique.
 *
 * @var \App\Models\Product $product
 * @var array<int, int>   $wishlistIds  identifiants des favoris de l'utilisateur
 */
$wishlistIds = $wishlistIds ?? [];
$isFavorite = in_array((int) $product->id, array_map('intval', $wishlistIds), true);
$category = $product->category();
$finalPrice = $product->effectivePrice();
?>
<div class="card card-product">
    <div class="product-image">
        <a href="<?= e(product_url($product)) ?>">
            <img src="<?= e(img($product->thumbnail)) ?>" alt="<?= e($product->name) ?>" loading="lazy">
        </a>
        <?php if ($product->hasDiscount()): ?>
            <span class="badge badge-sale">-<?= (int) round((float) $product->discount_percentage) ?>%</span>
        <?php endif; ?>
        <?php if ($product->is_new): ?>
            <span class="badge badge-new">Nouveau</span>
        <?php endif; ?>
        <form method="post" action="<?= e(url('/favoris/' . (int) $product->id)) ?>" class="favorite" data-wishlist>
            <?= csrf_field() ?>
            <button type="submit" title="<?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="bi <?= $isFavorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
            </button>
        </form>
    </div>

    <div class="product-info">
        <?php if ($category !== null): ?>
            <a class="small text-muted-2" href="<?= e(url('/categorie/' . (int) $category->id)) ?>"><?= e($category->name) ?></a>
        <?php endif; ?>

        <a class="fw-semibold text-body" href="<?= e(product_url($product)) ?>">
            <?= e(excerpt($product->name, 52)) ?>
        </a>

        <div class="rating">
            <?= stars((float) $product->rating_cache) ?>
            <span class="text-muted-2">(<?= (int) $product->review_count_cache ?>)</span>
        </div>

        <div class="d-flex align-items-baseline gap-2">
            <span class="price"><?= e(money($finalPrice)) ?></span>
            <?php if ($product->hasDiscount()): ?>
                <span class="price-old"><?= e(money((float) $product->price)) ?></span>
            <?php endif; ?>
        </div>

        <div class="d-flex gap-2 pt-2">
            <a href="<?= e(product_url($product)) ?>" class="btn btn-sm btn-outline-primary flex-grow-1">
                <i class="bi bi-eye me-1"></i>Voir
            </a>
            <?php if ($product->isInStock()): ?>
                <a href="<?= e(product_url($product)) ?>#panier" class="btn btn-sm btn-primary flex-grow-1">
                    <i class="bi bi-bag-plus me-1"></i>Ajouter
                </a>
            <?php else: ?>
                <button class="btn btn-sm btn-secondary flex-grow-1" disabled>Rupture</button>
            <?php endif; ?>
        </div>
    </div>
</div>