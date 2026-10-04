<?php
/**
 * Détail d'une commande + historique des statuts.
 */
$order = $order ?? null;
if ($order === null) {
    return;
}
$history = $history ?? [];
?>

<nav aria-label="fil d'ariane">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="<?= e(url('/compte')) ?>">Mon compte</a></li>
        <li class="breadcrumb-item"><a href="<?= e(url('/compte/commandes')) ?>">Commandes</a></li>
        <li class="breadcrumb-item active"><?= e($order->order_number) ?></li>
    </ol>
</nav>

<div class="orders-header">
    <h1 class="orders-title">Commande <?= e($order->order_number) ?></h1>
    <span class="badge badge-<?= e($order->statusBadge()) ?> fs-6"><?= e($order->statusLabel()) ?></span>
</div>

<div class="order-detail-layout">
    <div class="order-detail-main">
        <div class="panel mb-3">
            <h2 class="panel-title">Articles</h2>
            <?php foreach ($order->items as $item): ?>
                <div class="order-item">
                    <img src="<?= e(img($item->product_thumbnail)) ?>" alt="" class="thumb-sm">
                    <div class="flex-grow-1">
                        <a class="fw-semibold text-decoration-none text-body" href="<?= e(product_url($item->product_id)) ?>">
                            <?= e($item->product_name) ?>
                        </a>
                        <div class="small text-muted-2">
                            <?= (int) $item->quantity ?> × <?= e(money($item->unit_price)) ?>
                            <?php if (!empty($item->selected_size) || !empty($item->selected_color)): ?>
                                · <?= e(trim(implode(' · ', array_filter([$item->selected_size, $item->selected_color])))) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="fw-semibold"><?= e(money($item->totalPrice())) ?></span>
                </div>
            <?php endforeach; ?>

            <hr>

            <div class="summary-line"><span class="text-muted-2">Sous-total</span><span><?= e(money($order->subtotal)) ?></span></div>
            <div class="summary-line"><span class="text-muted-2">Livraison</span><span><?= e(money($order->shipping_fee)) ?></span></div>
            <?php if ((float) $order->discount_amount > 0): ?>
                <div class="summary-line text-success"><span>Remise</span><span>-<?= e(money($order->discount_amount)) ?></span></div>
            <?php endif; ?>
            <div class="summary-line summary-total"><span>Total</span><span><?= e(money($order->total)) ?></span></div>
        </div>

        <?php if ($history !== []): ?>
            <div class="panel">
                <h2 class="panel-title">Suivi</h2>
                <?php foreach ($history as $step): ?>
                    <div class="tracking-step">
                        <div class="tracking-icon">
                            <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center"
                                 style="width:32px;height:32px"><i class="bi bi-check-lg"></i></div>
                        </div>
                        <div class="tracking-content">
                            <div class="fw-semibold"><?= e(order_status_label($step['status'])) ?></div>
                            <div class="text-muted-2"><?= e(format_date($step['created_at'], 'd/m/Y H:i')) ?></div>
                            <?php if (!empty($step['note'])): ?>
                                <div class="text-muted-2"><?= e($step['note']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="order-detail-sidebar">
        <div class="panel mb-3">
            <h2 class="panel-title"><i class="bi bi-geo-alt me-1"></i>Adresse de livraison</h2>
            <div class="small text-muted-2">
                <?= e($order->ship_full_name) ?><br>
                <?= e($order->ship_address) ?><br>
                <?= e(trim((string) $order->ship_city . ' ' . (string) $order->ship_zip_code)) ?><br>
                <?= e($order->ship_country) ?><br>
                <?= e($order->ship_phone) ?>
            </div>
        </div>

        <div class="panel mb-3">
            <h2 class="panel-title"><i class="bi bi-credit-card me-1"></i>Paiement</h2>
            <div class="small text-muted-2">
                Moyen : <strong><?= e($order->payment_method) ?></strong><br>
                Statut :
                <span class="badge badge-<?= $order->is_paid ? 'sale' : 'new' ?>">
                    <?= $order->is_paid ? 'Payée' : 'En attente' ?>
                </span>
                <?php if (!empty($order->tracking_number)): ?>
                    <br>Suivi : <strong><?= e($order->tracking_number) ?></strong>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($order->canBeCancelled()): ?>
            <form method="post" action="<?= e(url('/compte/commandes/' . (int) $order->id . '/annuler')) ?>"
                  onsubmit="return confirm('Annuler cette commande ?');">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger w-100" type="submit">Annuler la commande</button>
            </form>
        <?php endif; ?>
    </div>
</div>