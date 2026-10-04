<?php
/**
 * Panier : lignes, code promo, récapitulatif.
 */
$cart = $cart ?? null;
$summary = $summary ?? [];
$coupons = $coupons ?? [];
$items = $cart === null ? [] : $cart->items;
?>

<h1 class="h3 fw-bold mb-4">Mon panier</h1>

<?php if ($items === []): ?>
    <div class="panel text-center py-5">
        <i class="bi bi-bag-x fs-1 text-muted-2"></i>
        <h2 class="h5 mt-3">Votre panier est vide</h2>
        <p class="text-muted-2">Parcourez notre catalogue pour découvrir nos produits.</p>
        <a class="btn btn-primary" href="<?= e(url('/boutique')) ?>">Découvrir la boutique</a>
    </div>
<?php else: ?>
    <div class="cart-layout">
        <div class="cart-items">
            <div class="panel">
                <div class="cart-header">
                    <span class="fw-semibold"><?= (int) ($summary['totalItems'] ?? 0) ?> article(s)</span>
                    <form method="post" action="<?= e(url('/panier/vider')) ?>" class="cart-clear"
                          onsubmit="return confirm('Vider entièrement le panier ?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-sm btn-outline-danger" type="submit">
                            <i class="bi bi-trash me-1"></i>Vider le panier
                        </button>
                    </form>
                </div>

                <div class="cart-table">
                    <div class="cart-row cart-header-row">
                        <div>Produit</div>
                        <div class="text-center">Prix</div>
                        <div class="text-center">Quantité</div>
                        <div class="text-end">Total</div>
                        <div></div>
                    </div>
                    <?php foreach ($items as $item): ?>
                        <?php $product = $item->product(); ?>
                        <div class="cart-row">
                            <div class="cart-product">
                                <img src="<?= e(img($product?->thumbnail)) ?>" alt="" class="thumb-sm">
                                <div>
                                    <a class="fw-semibold text-decoration-none text-body"
                                       href="<?= e(product_url($product)) ?>">
                                        <?= e($product?->name ?? 'Article indisponible') ?>
                                    </a>
                                    <?php if ($item->label() !== ''): ?>
                                        <div class="small text-muted-2"><?= e($item->label()) ?></div>
                                    <?php endif; ?>
                                    <?php if ($product !== null && (int) $product->stock < (int) $item->quantity): ?>
                                        <div class="small text-danger">Stock insuffisant</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-center"><?= e(money($item->unitPrice())) ?></div>
                            <div class="text-center">
                                <form method="post" action="<?= e(url('/panier/modifier/' . (int) $item->id)) ?>">
                                    <?= csrf_field() ?>
                                    <input type="number" class="qty-input"
                                           name="quantity" value="<?= (int) $item->quantity ?>" min="1"
                                           max="<?= max(1, (int) ($product?->stock ?? 1)) ?>" data-qty-input>
                                </form>
                            </div>
                            <div class="text-end fw-semibold"><?= e(money($item->totalPrice())) ?></div>
                            <div class="text-end">
                                <form method="post" action="<?= e(url('/panier/supprimer/' . (int) $item->id)) ?>" class="cart-remove">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Retirer">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="panel">
                <h2 class="panel-title">Code promo</h2>
                <?php if (!empty($cart->coupon_code)): ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="badge badge-soft fs-6"><?= e($cart->coupon_code) ?></span>
                        <form method="post" action="<?= e(url('/panier/code/retirer')) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Retirer</button>
                        </form>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/panier/code')) ?>" class="coupon-form">
                        <?= csrf_field() ?>
                        <div class="coupon-input">
                            <input type="text" name="code" class="form-control" placeholder="Entrez votre code promo"
                                   value="<?= e(old('code')) ?>" required>
                        </div>
                        <div class="coupon-btn">
                            <button class="btn btn-primary" type="submit">Appliquer</button>
                        </div>
                    </form>

                    <?php if ($coupons !== []): ?>
                        <div class="available-coupons">
                            <span class="text-muted-2">Vos codes disponibles :</span>
                            <?php foreach (array_slice($coupons, 0, 4) as $coupon): ?>
                                <form method="post" action="<?= e(url('/panier/code')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="code" value="<?= e($coupon->code) ?>">
                                    <button class="btn btn-link btn-sm p-0 me-2" type="submit"><?= e($coupon->code) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <? endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="cart-summary">
            <div class="panel" style="position:sticky;top:90px">
                <h2 class="panel-title">Récapitulatif</h2>

                <div class="summary-line">
                    <span class="text-muted-2">Sous-total</span>
                    <span><?= e(money($summary['subtotal'] ?? 0)) ?></span>
                </div>
                <div class="summary-line">
                    <span class="text-muted-2">Livraison</span>
                    <span><?= ($summary['shippingFee'] ?? 0) > 0 ? e(money($summary['shippingFee'])) : 'Offerte' ?></span>
                </div>
                <?php if (($summary['discountAmount'] ?? 0) > 0): ?>
                    <div class="summary-line text-success">
                        <span>Remise (<?= e((string) $cart->coupon_code) ?>)</span>
                        <span>-<?= e(money($summary['discountAmount'])) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-line summary-total">
                    <span>Total</span>
                    <span><?= e(money($summary['total'] ?? 0)) ?></span>
                </div>

                <a class="btn btn-primary w-100 mt-3" href="<?= e(url('/commande')) ?>">
                    <i class="bi bi-bag-check me-1"></i>Finaliser la commande
                </a>
                <a class="btn btn-outline-secondary w-100 mt-2" href="<?= e(url('/boutique')) ?>">
                    Continuer mes achats
                </a>

                <?php $restant = free_shipping_from() - (float) ($summary['subtotal'] ?? 0); ?>
                <?php if ($restant > 0): ?>
                    <p class="small text-muted-2 mt-3 mb-0">
                        Plus que <strong><?= e(money($restant)) ?></strong> pour bénéficier de la livraison offerte.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>