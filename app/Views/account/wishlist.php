<?php
/**
 * Favoris du client.
 */
use App\Core\View;

$products = $products ?? [];
?>

<h1 class="h4 fw-bold mb-4">Mes favoris</h1>

<?php if ($products === []): ?>
    <div class="panel text-center py-5">
        <i class="bi bi-heart fs-1 text-muted-2"></i>
        <p class="mt-3 mb-3">Vous n'avez pas encore ajouté de produit en favori.</p>
        <a class="btn btn-primary" href="<?= e(url('/boutique')) ?>">Découvrir la boutique</a>
    </div>
<?php else: ?>
    <div class="wishlist-grid">
        <?php foreach ($products as $product): ?>
            <?php View::partial('partials.product-card', [
                'product'     => $product,
                'wishlistIds' => [(int) $product->id],
            ]); ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>