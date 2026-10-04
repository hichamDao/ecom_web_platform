<?php
/**
 * Fiche produit : galerie, prix, ajout au panier, avis clients.
 */
use App\Core\View;

$product = $product ?? null;
if ($product === null) {
    return;
}

$category = $product->category();
$images = $product->imageList();
$attributes = is_array($product->attributes) ? $product->attributes : [];
$finalPrice = $product->effectivePrice();
$ratings = [5 => 5, 4 => 4, 3 => 3, 2 => 2, 1 => 1];
?>

<nav aria-label="fil d'ariane">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>">Accueil</a></li>
        <li class="breadcrumb-item"><a href="<?= e(url('/boutique')) ?>">Boutique</a></li>
        <?php if ($category !== null): ?>
            <li class="breadcrumb-item"><a href="<?= e(url('/categorie/' . (int) $category->id)) ?>"><?= e($category->name) ?></a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page"><?= e(excerpt($product->name, 40)) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="product-gallery">
            <img id="product-main-image" class="main-img" src="<?= e(img($images[0] ?? $product->thumbnail)) ?>"
                 alt="<?= e($product->name) ?>">
            <?php if (count($images) > 1): ?>
                <div class="thumb-row d-flex gap-2 flex-wrap mt-3">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?= e(img($image)) ?>" data-full="<?= e(img($image)) ?>"
                             class="<?= $index === 0 ? 'active' : '' ?>" alt="Vue <?= $index + 1 ?>">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <?php if ($category !== null): ?>
            <a class="badge badge-soft text-decoration-none mb-2 d-inline-block"
               href="<?= e(url('/categorie/' . (int) $category->id)) ?>"><?= e($category->name) ?></a>
        <?php endif; ?>

        <h1 class="h3 fw-bold mb-2"><?= e($product->name) ?></h1>

        <div class="d-flex align-items-center gap-3 mb-3">
            <?= stars((float) $product->rating_cache) ?>
            <span class="small text-muted-2">
                <?= number_format((float) $product->rating_cache, 1) ?> · <?= (int) $product->review_count_cache ?> avis
            </span>
            <?php if (!empty($product->brand)): ?>
                <span class="small text-muted-2">Marque : <strong><?= e($product->brand) ?></strong></span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-baseline gap-3 mb-3">
            <span class="fs-2 fw-bold"><?= e(money($finalPrice)) ?></span>
            <?php if ($product->hasDiscount()): ?>
                <span class="price-old fs-5"><?= e(money((float) $product->price)) ?></span>
                <span class="badge badge-sale">
                    -<?= (float) $product->discount_percentage ?>%
                </span>
            <?php endif; ?>
        </div>

        <p class="text-muted-2"><?= e($product->description) ?></p>

        <div class="d-flex flex-wrap gap-3 mb-3 small">
            <span class="<?= $product->isInStock() ? 'text-success' : 'text-danger' ?>">
                <i class="bi bi-<?= $product->isInStock() ? 'check-circle' : 'x-circle' ?> me-1"></i>
                <?= $product->isInStock() ? 'En stock (' . (int) $product->stock . ')' : 'Rupture de stock' ?>
            </span>
            <span class="text-muted-2"><i class="bi bi-eye me-1"></i><?= (int) $product->views ?> vue(s)</span>
            <span class="text-muted-2"><i class="bi bi-bag-check me-1"></i><?= (int) $product->sold_count ?> vendu(s)</span>
            <span class="text-muted-2"><i class="bi bi-truck me-1"></i>Livraison <?= $product->effectivePrice() >= free_shipping_from() ? 'offerte' : e(money(shipping_fee())) ?></span>
        </div>

        <?php if ($product->isLowStock()): ?>
            <div class="alert alert-warning py-2 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Plus que <?= (int) $product->stock ?> exemplaire(s) en stock !
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/panier/ajouter')) ?>" class="panel mb-3" id="panier">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product->id ?>">

            <div class="row g-2 align-items-end">
                <?php if (!empty($attributes['tailles'])): ?>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold" for="taille">Taille</label>
                        <select class="form-select form-select-sm" name="taille" id="taille">
                            <?php foreach ((array) $attributes['tailles'] as $size): ?>
                                <option value="<?= e($size) ?>"><?= e($size) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <?php if (!empty($attributes['couleurs'])): ?>
                    <div class="col-6 col-md-4">
                        <label class="form-label small fw-semibold" for="couleur">Couleur</label>
                        <select class="form-select form-select-sm" name="couleur" id="couleur">
                            <?php foreach ((array) $attributes['couleurs'] as $color): ?>
                                <option value="<?= e($color) ?>"><?= e($color) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-6 col-md-4">
                    <label class="form-label small fw-semibold" for="quantity">Quantité</label>
                    <input type="number" class="form-control form-control-sm" id="quantity" name="quantity"
                           value="1" min="1" max="<?= max(1, (int) $product->stock) ?>">
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-primary flex-grow-1" type="submit" <?= $product->isInStock() ? '' : 'disabled' ?>>
                    <i class="bi bi-bag-plus me-1"></i>Ajouter au panier
                </button>
                <button class="btn btn-outline-primary flex-grow-1" type="submit" name="acheter" value="1"
                        <?= $product->isInStock() ? '' : 'disabled' ?>>Acheter</button>
            </div>
        </form>

        <div class="d-flex gap-2 mb-3">
            <form method="post" action="<?= e(url('/favoris/' . (int) $product->id)) ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger w-100" type="submit">
                    <i class="bi <?= !empty($inWishlist) ? 'bi-heart-fill' : 'bi-heart' ?> me-1"></i>
                    <?= !empty($inWishlist) ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>
                </button>
            </form>
            <a class="btn btn-outline-secondary flex-grow-1" href="<?= e(url('/compte/favoris')) ?>">
                <i class="bi bi-list-ul me-1"></i>Voir mes favoris
            </a>
        </div>

        <?php if ($attributes !== []): ?>
            <div class="panel mb-3">
                <h2 class="h6 fw-bold mb-2">Caractéristiques</h2>
                <table class="table table-sm mb-0 small">
                    <?php foreach ($attributes as $key => $value): ?>
                        <tr>
                            <th scope="row" class="text-muted-2 fw-normal" style="width:40%"><?= e(ucfirst(str_replace('_', ' ', (string) $key))) ?></th>
                            <td><?= e(is_array($value) ? implode(', ', $value) : $value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($product->hasTryOn()): ?>
            <div class="alert alert-info d-flex align-items-center gap-2">
                <i class="bi bi-camera"></i>
                <span>Essayage virtuel disponible pour ce produit.</span>
                <img src="<?= e(img($product->overlay_image)) ?>" alt="" height="40" class="rounded">
            </div>
        <?php endif; ?>
    </div>
</div>

<section class="mt-5">
    <h2 class="section-title">Avis clients (<?= (int) $product->review_count_cache ?>)</h2>

    <?php if (auth_check() && empty($userReview)): ?>
        <div class="panel mb-4">
            <h3 class="h6 fw-bold mb-3">Donner mon avis</h3>
            <form method="post" action="<?= e(url('/produit/' . (int) $product->id . '/avis')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="rating">Note</label>
                    <select class="form-select form-select-sm" id="rating" name="rating" style="width:160px">
                        <?php foreach ($ratings as $label => $value): ?>
                            <option value="<?= $value ?>"><?= $value ?> étoile(s)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="comment">Votre commentaire</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3"
                              placeholder="Qu'avez-vous aimé ? (5 caractères minimum)"><?= e(old('comment')) ?></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Envoyer pour modération</button>
            </form>
        </div>
    <?php elseif (auth_check() && !empty($userReview)): ?>
        <div class="alert alert-info small">Vous avez déjà publié un avis sur ce produit.</div>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
        <div class="panel text-center py-4">
            <p class="text-muted-2 mb-0">Aucun avis publié pour le moment.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($reviews as $review): ?>
                <div class="col-md-6">
                    <div class="panel h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong><?= e($review->authorName()) ?></strong>
                                <?php if ($review->is_verified_purchase): ?>
                                    <span class="badge badge-soft ms-1">Achat vérifié</span>
                                <?php endif; ?>
                            </div>
                            <?= stars((float) $review->rating) ?>
                        </div>
                        <p class="mb-1"><?= e($review->comment) ?></p>
                        <small class="text-muted-2"><?= e(format_date($review->created_at, 'd/m/Y')) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if (!empty($related)): ?>
    <section class="mt-5">
        <h2 class="section-title">Produits similaires</h2>
        <div class="row g-3">
            <?php foreach ($related as $item): ?>
                <div class="col-6 col-md-3">
                    <?php View::partial('partials.product-card', [
                        'product'    => $item,
                        'wishlistIds' => [$product->id],
                    ]); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>