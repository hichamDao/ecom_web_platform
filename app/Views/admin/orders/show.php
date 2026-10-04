<?php
/**
 * Back-office : détail d'une commande + actions (statut, paiement, suivi).
 */
$order = $order ?? null;
if ($order === null) {
    return;
}
$history = $history ?? [];
$statuses = order_statuses();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/commandes')) ?>">
        <i class="bi bi-arrow-left me-1"></i>Retour à la liste
    </a>
    <span class="badge bg-<?= e($order->statusBadge()) ?> fs-6"><?= e($order->statusLabel()) ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Articles commandés</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Produit</th><th class="text-center">Qté</th><th class="text-end">Prix unitaire</th><th class="text-end">Total</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($order->items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= e(img($item->product_thumbnail)) ?>" alt="" class="thumb-sm">
                                    <div>
                                        <div class="fw-semibold"><?= e($item->product_name) ?></div>
                                        <?php if (!empty($item->selected_size) || !empty($item->selected_color)): ?>
                                            <small class="text-muted-2">
                                                <?= e(trim(implode(' · ', array_filter([$item->selected_size, $item->selected_color])))) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center"><?= (int) $item->quantity ?></td>
                            <td class="text-end"><?= e(money($item->unit_price)) ?></td>
                            <td class="text-end fw-semibold"><?= e(money($item->totalPrice())) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="text-end text-muted-2">Sous-total</td><td class="text-end"><?= e(money($order->subtotal)) ?></td></tr>
                        <tr><td colspan="3" class="text-end text-muted-2">Livraison</td><td class="text-end"><?= e(money($order->shipping_fee)) ?></td></tr>
                        <?php if ((float) $order->discount_amount > 0): ?>
                            <tr><td colspan="3" class="text-end text-success">Remise</td><td class="text-end text-success">-<?= e(money($order->discount_amount)) ?></td></tr>
                        <?php endif; ?>
                        <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= e(money($order->total)) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Historique</h2>
            <?php if ($history === []): ?>
                <p class="text-muted-2 small mb-0">Aucun changement de statut enregistré.</p>
            <?php else: ?>
                <?php foreach ($history as $step): ?>
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span>
                            <span class="badge bg-<?= e(order_status_badge($step['status'])) ?> me-2"><?= e(order_status_label($step['status'])) ?></span>
                            <?= e($step['note'] ?? '') ?>
                        </span>
                        <span class="text-muted-2"><?= e(format_date($step['created_at'], 'd/m/Y H:i')) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Client</h2>
            <div class="small text-muted-2">
                <?= e($order->customerName()) ?><br>
                <?= e($order->customer_email) ?><br>
                <?= e(format_date($order->created_at, 'd/m/Y H:i')) ?>
            </div>
            <?php if ($order->user_id !== null): ?>
                <a class="btn btn-sm btn-outline-primary w-100 mt-2"
                   href="<?= e(url('/admin/clients/' . (int) $order->user_id)) ?>">Voir la fiche client</a>
            <?php endif; ?>
        </div>

        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Adresse de livraison</h2>
            <div class="small text-muted-2">
                <?= e($order->ship_full_name) ?><br>
                <?= e($order->ship_address) ?><br>
                <?= e(trim((string) $order->ship_city . ' ' . (string) $order->ship_zip_code)) ?><br>
                <?= e($order->ship_country) ?><br>
                <?= e($order->ship_phone) ?>
            </div>
            <?php if (!empty($order->notes)): ?>
                <hr>
                <div class="small"><strong>Instructions :</strong> <?= e($order->notes) ?></div>
            <?php endif; ?>
        </div>

        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Mettre à jour le statut</h2>
            <form method="post" action="<?= e(url('/admin/commandes/' . (int) $order->id . '/statut')) ?>">
                <?= csrf_field() ?>
                <select class="form-select form-select-sm mb-2" name="status">
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= e($key) ?>" <?= selected($order->status, $key) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" class="form-control form-control-sm mb-2" name="tracking_number"
                       placeholder="Numéro de suivi (optionnel)" value="<?= e($order->tracking_number) ?>">
                <input type="text" class="form-control form-control-sm mb-2" name="note"
                       placeholder="Note (visible par le client)">
                <button class="btn btn-primary btn-sm w-100" type="submit">Enregistrer</button>
            </form>
        </div>

        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Paiement</h2>
            <p class="small">
                Moyen : <strong><?= e($order->payment_method) ?></strong><br>
                Statut actuel :
                <span class="badge bg-<?= $order->is_paid ? 'success' : 'warning' ?>">
                    <?= $order->is_paid ? 'Payée' : 'En attente' ?>
                </span>
            </p>
            <form method="post" action="<?= e(url('/admin/commandes/' . (int) $order->id . '/paiement')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="is_paid" value="<?= $order->is_paid ? '0' : '1' ?>">
                <button class="btn btn-sm w-100 <?= $order->is_paid ? 'btn-outline-danger' : 'btn-success' ?>" type="submit">
                    <?= $order->is_paid ? 'Marquer comme non payée' : 'Marquer comme payée' ?>
                </button>
            </form>
        </div>
    </div>
</div>