<?php
/**
 * Accueil : bannières, catégories, sélections et codes promo.
 */
use App\Core\View;

$wishlistIds = $wishlistIds ?? [];
$banners = $banners ?? [];
$featured = $featured ?? [];
$newArrivals = $newArrivals ?? [];
$bestSellers = $bestSellers ?? [];
$coupons = $coupons ?? [];
$categories = $categories ?? [];
$hero = $banners[0] ?? null;
?>

<?php if ($hero !== null): ?>
    <section class="hero">
        <?php if (!empty($hero->photo)): ?>
            <img src="<?= e(img($hero->photo)) ?>" alt="<?= e($hero->title) ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
        <?php endif; ?>
        <div class="hero-content">
            <span class="hero-subtitle">Offre du moment</span>
            <h2><?= e($hero->title) ?></h2>
            <p><?= e($hero->subtitle) ?></p>
            <a href="<?= e(url($hero->link ?: '/boutique')) ?>" class="btn-primary">
                <?= e($hero->button_text) ?> <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </section>
<?php endif; ?>

<?php if (count($banners) > 1): ?>
    <div class="banners-grid">
        <?php foreach (array_slice($banners, 1, 3) as $banner): ?>
            <a class="banner-card" href="<?= e(url($banner->link ?: '/boutique')) ?>">
                <?php if (!empty($banner->photo)): ?>
                    <img src="<?= e(img($banner->photo)) ?>" alt="<?= e($banner->title) ?>">
                <?php endif; ?>
                <div class="banner-content">
                    <div class="banner-badge"><?= e($banner->title) ?></div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($categories !== []): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">Catégories</h2>
            <a class="view-all" href="<?= e(url('/boutique')) ?>">Tout voir <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="categories">
            <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                <a class="category" href="<?= e(url('/categorie/' . (int) $category['id'])) ?>">
                    <div class="category-image">
                        <img src="<?= e(img($category['image'])) ?>" alt="<?= e($category['name']) ?>">
                    </div>
                    <span><?= e($category['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($featured !== []): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-stars text-warning me-1"></i>Produits en vedette</h2>
            <a class="view-all" href="<?= e(url('/boutique')) ?>">Tout voir</a>
        </div>
        <div class="products-grid">
            <?php foreach ($featured as $product): ?>
                <?php View::partial('partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($newArrivals !== []): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-clock-history me-1"></i>Nouveautés</h2>
            <a class="view-all" href="<?= e(url('/boutique')) ?>">Tout voir</a>
        </div>
        <div class="products-grid">
            <?php foreach ($newArrivals as $product): ?>
                <?php View::partial('partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($coupons !== []): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-ticket-perforated me-1"></i>Vos codes promo</h2>
        </div>
        <div class="products-grid">
            <?php foreach (array_slice($coupons, 0, 4) as $coupon): ?>
                <div class="panel h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge badge-soft mb-2">-<?= (float) $coupon->percentage ?>%</span>
                            <i class="bi bi-ticket-perforated text-muted-2"></i>
                        </div>
                        <div class="fw-bold fs-5"><?= e($coupon->code) ?></div>
                        <div class="small text-muted-2">
                            <?php if ($coupon->min_order_amount !== null): ?>
                                dès <?= e(money($coupon->min_order_amount)) ?>
                            <?php endif; ?>
                            <?php if ($coupon->expires_at !== null): ?>
                                · jusqu'au <?= e(format_date($coupon->expires_at, 'd/m/Y')) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a class="btn btn-sm btn-outline-primary mt-2" href="<?= e(url('/panier')) ?>">Utiliser</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ($bestSellers !== []): ?>
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><i class="bi bi-fire text-danger me-1"></i>Meilleures ventes</h2>
            <a class="view-all" href="<?= e(url('/boutique')) ?>">Tout voir</a>
        </div>
        <div class="products-grid">
            <?php foreach ($bestSellers as $product): ?>
                <?php View::partial('partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds]); ?>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>