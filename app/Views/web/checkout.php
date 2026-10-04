<?php
/**
 * Tunnel de commande : adresse de livraison + paiement.
 */
$cart = $cart ?? null;
$summary = $summary ?? [];
$addresses = $addresses ?? [];
$coupons = $coupons ?? [];
$methods = $methods ?? ['CARD', 'CASH'];
$items = $cart === null ? [] : $cart->items;

$methodLabels = [
    'CARD'     => 'Carte bancaire',
    'CASH'     => 'Paiement à la livraison',
    'TRANSFER' => 'Virement bancaire',
    'PAYPAL'   => 'PayPal',
];
?>

<h1 class="h3 fw-bold mb-4">Finaliser la commande</h1>

<form method="post" action="<?= e(url('/commande')) ?>">
    <?= csrf_field() ?>
    <div class="checkout-layout">
        <div class="checkout-main">
            <div class="panel mb-3">
                <h2 class="panel-title"><i class="bi bi-geo-alt me-1"></i>Adresse de livraison</h2>

                <?php if ($addresses !== []): ?>
                    <div class="address-list">
                        <?php foreach ($addresses as $index => $address): ?>
                            <label class="address-card">
                                <input class="form-check-input" type="radio" name="adresse_id"
                                       id="adresse<?= (int) $address->id ?>" value="<?= (int) $address->id ?>"
                                    <?= $address->is_default || $index === 0 ? 'checked' : '' ?>>
                                <div class="address-content">
                                    <strong><?= e($address->full_name) ?></strong> — <?= e($address->formatted()) ?>
                                    <span class="text-muted-2 small">(<?= e($address->phone) ?>)</span>
                                    <?php if ($address->is_default): ?>
                                        <span class="badge badge-soft ms-1">Par défaut</span>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>

                        <label class="address-card address-new">
                            <input class="form-check-input" type="radio" name="adresse_id" id="adresseNouvelle" value="">
                            <div class="address-content">
                                <span class="fw-semibold">Utiliser une nouvelle adresse</span>
                            </div>
                        </label>
                    </div>
                <?php endif; ?>

                <div id="nouvelleAdresse" class="mt-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="fullName">Nom complet *</label>
                            <input type="text" class="form-control<?= old_has('full_name') ?>" id="fullName"
                                   name="fullName" value="<?= e(old('full_name', auth()?->fullName())) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="phone">Téléphone *</label>
                            <input type="text" class="form-control" id="phone" name="phone"
                                   value="<?= e(old('phone', auth()?->phone)) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="address">Adresse *</label>
                            <input type="text" class="form-control" id="address" name="address"
                                   value="<?= e(old('address')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="city">Ville *</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?= e(old('city')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="state">Région</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?= e(old('state')) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold" for="zipCode">Code postal</label>
                            <input type="text" class="form-control" id="zipCode" name="zipCode" value="<?= e(old('zipCode')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="country">Pays *</label>
                            <input type="text" class="form-control" id="country" name="country"
                                   value="<?= e(old('country', 'Maroc')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="notes">Instructions (optionnel)</label>
                            <input type="text" class="form-control" id="notes" name="notes" value="<?= e(old('notes')) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2 class="panel-title"><i class="bi bi-credit-card me-1"></i>Moyen de paiement</h2>
                <?php foreach ($methods as $method): ?>
                    <label class="payment-option">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="pay<?= e($method) ?>"
                               value="<?= e($method) ?>" <?= $method === 'CARD' ? 'checked' : '' ?>>
                        <span><?= e($methodLabels[$method] ?? $method) ?></span>
                    </label>
                <?php endforeach; ?>
                <p class="small text-muted-2 mb-0 mt-2">
                    <i class="bi bi-info-circle me-1"></i>Démonstration : aucun paiement réel n'est effectué.
                </p>
            </div>
        </div>

        <div class="checkout-sidebar">
            <div class="panel" style="position:sticky;top:90px">
                <h2 class="panel-title">Votre commande (<?= count($items) ?> article(s))</h2>

                <?php foreach ($items as $item): ?>
                    <?php $product = $item->product(); ?>
                    <div class="checkout-item">
                        <img src="<?= e(img($product?->thumbnail)) ?>" alt="" class="checkout-thumb">
                        <div class="flex-grow-1 small">
                            <div class="fw-semibold"><?= e(excerpt($product?->name ?? 'Article', 34)) ?></div>
                            <div class="text-muted-2"><?= (int) $item->quantity ?> × <?= e(money($item->unitPrice())) ?></div>
                        </div>
                        <span class="small fw-semibold"><?= e(money($item->totalPrice())) ?></span>
                    </div>
                <?php endforeach; ?>

                <hr>

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
                        <span>Remise</span><span>-<?= e(money($summary['discountAmount'])) ?></span>
                    </div>
                <?php endif; ?>
                <div class="summary-line summary-total">
                    <span>Total à payer</span>
                    <span><?= e(money($summary['total'] ?? 0)) ?></span>
                </div>

                <button class="btn btn-primary w-100 mt-3" type="submit">
                    <i class="bi bi-check2-circle me-1"></i>Confirmer la commande
                </button>
                <a class="btn btn-outline-secondary w-100 mt-2" href="<?= e(url('/panier')) ?>">Modifier le panier</a>
            </div>
        </div>
    </div>
</form>