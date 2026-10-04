<?php
/**
 * Back-office : liste des commandes avec filtres.
 */
use App\Core\View;

$paginator = $paginator ?? null;
$filters = $filters ?? [];
$statuses = $statuses ?? [];
$orders = $paginator === null ? [] : $paginator->items;
?>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
        <input type="search" name="q" class="form-control form-control-sm" placeholder="N° de commande, e-mail, client"
               value="<?= e($filters['keyword'] ?? '') ?>">
    </div>
    <div class="col-md-2">
        <select name="statut" class="form-select form-select-sm" data-auto-submit>
            <option value="">Tous les statuts</option>
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?= e($key) ?>" <?= selected($filters['status'] ?? '', $key) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="paiement" class="form-select form-select-sm" data-auto-submit>
            <option value="">Paiement : tous</option>
            <option value="1" <?= selected($filters['isPaid'] ?? '', '1') ?>>Payées</option>
            <option value="0" <?= selected($filters['isPaid'] ?? '', '0') ?>>Non payées</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" name="du" class="form-control form-control-sm" value="<?= e($filters['from'] ?? '') ?>" title="Du">
    </div>
    <div class="col-md-2">
        <input type="date" name="au" class="form-control form-control-sm" value="<?= e($filters['to'] ?? '') ?>" title="Au">
    </div>
    <div class="col-md-1">
        <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="table-card">
    <?php if ($orders === []): ?>
        <div class="text-center py-5">
            <i class="bi bi-bag fs-1 text-muted-2"></i>
            <p class="mt-3 mb-0 text-muted-2">Aucune commande ne correspond à ces filtres.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Client</th>
                        <th>Articles</th>
                        <th class="text-end">Montant</th>
                        <th class="text-center">Paiement</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($order->order_number) ?></td>
                        <td class="small">
                            <?= e($order->customerName()) ?><br>
                            <span class="text-muted-2"><?= e($order->customer_email) ?></span>
                        </td>
                        <td class="text-center small"><?= count($order->items) ?></td>
                        <td class="text-end fw-semibold"><?= e(money($order->total)) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $order->is_paid ? 'success' : 'warning' ?>">
                                <?= $order->is_paid ? 'Payée' : 'En attente' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= e($order->statusBadge()) ?>"><?= e($order->statusLabel()) ?></span>
                        </td>
                        <td class="text-end small text-muted-2"><?= e(format_date($order->created_at, 'd/m/Y H:i')) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/commandes/' . (int) $order->id)) ?>">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php View::partial('partials.pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>