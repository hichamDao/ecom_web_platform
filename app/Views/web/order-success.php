<?php
/**
 * Confirmation de commande.
 */
$order = $order ?? null;
if ($order === null) {
    return;
}
?>

<div class="text-center py-4">
    <div class="order-success-icon">
        <i class="bi bi-check-lg"></i>
    </div>
    <h1 class="h3 fw-bold mb-2">Merci pour votre commande !</h1>
    <p class="text-muted-2 mb-4">
        Votre commande <strong><?= e($order->order_number) ?></strong> a bien été enregistrée.
        Vous recevrez une confirmation par e-mail.
    </p>
    <div class="d-flex gap-2 justify-content-center">
        <a class="btn btn-primary" href="<?= e(url('/compte/commandes/' . (int) $order->id)) ?>">
            <i class="bi bi-receipt me-1"></i>Voir le détail
        </a>
        <a class="btn btn-outline-secondary" href="<?= e(url('/boutique')) ?>">Continuer mes achats</a>
    </div>
</div>

<div class="section">
    <div class="panel">
        <h2 class="panel-title">Récapitulatif</h2>

        <?php foreach ($order->items as $item): ?>
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="<?= e(img($item->product_thumbnail)) ?>" alt="" width="46" height="46" class="rounded">
                <div class="flex-grow-1 small">
                    <div class="fw-semibold"><?= e($item->product_name) ?></div>
                    <div class="text-muted-2"><?= (int) $item->quantity ?> × <?= e(money($item->unit_price)) ?></div>
                </div>
                <span class="small fw-semibold"><?= e(money($item->totalPrice())) ?></span>
            </div>
        <?php endforeach; ?>

        <hr>

        <div class="summary-line"><span class="text-muted-2">Sous-total</span><span><?= e(money($order->subtotal)) ?></span></div>
        <div class="summary-line"><span class="text-muted-2">Livraison</span><span><?= e(money($order->shipping_fee)) ?></span></div>
        <?php if ((float) $order->discount_amount > 0): ?>
            <div class="summary-line text-success"><span>Remise</span><span>-<?= e(money($order->discount_amount)) ?></span></div>
        <?php endif; ?>
        <div class="summary-line summary-total"><span>Total</span><span><?= e(money($order->total)) ?></span></div>

        <div class="order-detail-grid mt-3">
            <div class="order-detail-card">
                <div class="fw-semibold small mb-1"><i class="bi bi-geo-alt me-1"></i>Livraison</div>
                <div class="small text-muted-2">
                    <?= e($order->ship_full_name) ?><br>
                    <?= e($order->ship_address) ?><br>
                    <?= e(trim((string) $order->ship_city . ' ' . (string) $order->ship_zip_code)) ?><br>
                    <?= e($order->ship_country) ?><br>
                    <?= e($order->ship_phone) ?>
                </div>
            </div>
            <div class="order-detail-card">
                <div class="fw-semibold small mb-1"><i class="bi bi-credit-card me-1"></i>Paiement</div>
                <div class="small text-muted-2">
                    <?= e($order->payment_method) ?><br>
                    Statut :
                    <span class="badge badge-<?= $order->is_paid ? 'sale' : 'new' ?>">
                        <?= $order->is_paid ? 'Payée' : 'En attente' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>