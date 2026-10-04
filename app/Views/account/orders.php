<?php
/**
 * Liste des commandes du client.
 */
$orders = $orders ?? [];
?>

<div class="orders-header">
    <h1 class="orders-title">Mes commandes</h1>
    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/boutique')) ?>">Continuer mes achats</a>
</div>

<?php if ($orders === []): ?>
    <div class="panel text-center py-5">
        <i class="bi bi-bag fs-1 text-muted-2"></i>
        <p class="mt-3 mb-3">Vous n'avez pas encore passé de commande.</p>
        <a class="btn btn-primary" href="<?= e(url('/boutique')) ?>">Découvrir nos produits</a>
    </div>
<?php else: ?>
    <div class="orders-list">
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <strong><?= e($order->order_number) ?></strong>
                        <span class="text-muted-2 small ms-2"><?= e(format_date($order->created_at, 'd/m/Y H:i')) ?></span>
                    </div>
                    <span class="badge badge-<?= e($order->statusBadge()) ?>"><?= e($order->statusLabel()) ?></span>
                </div>

                <div class="order-card-items">
                    <?php foreach ($order->items as $item): ?>
                        <img src="<?= e(img($item->product_thumbnail)) ?>" alt="" width="52" height="52" class="rounded">
                    <?php endforeach; ?>
                </div>

                <div class="order-card-footer">
                    <span class="small text-muted-2">
                        <?= count($order->items) ?> article(s) ·
                        <?= $order->is_paid ? 'Paiement confirmé' : 'Paiement en attente' ?>
                    </span>
                    <span class="fw-bold"><?= e(money($order->total)) ?></span>
                </div>

                <div class="order-card-actions">
                    <a class="btn btn-sm btn-primary" href="<?= e(url('/compte/commandes/' . (int) $order->id)) ?>">
                        <i class="bi bi-eye me-1"></i>Détails
                    </a>
                    <?php if ($order->canBeCancelled()): ?>
                        <form method="post" action="<?= e(url('/compte/commandes/' . (int) $order->id . '/annuler')) ?>"
                              onsubmit="return confirm('Annuler cette commande ?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">Annuler</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>