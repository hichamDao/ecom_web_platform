<?php
/**
 * Back-office : fiche d'un client et de ses commandes.
 *
 * @var \App\Models\User $user
 * @var array<int, \App\Models\Order> $orders
 * @var array<string, int|float> $stats
 */
$initials = $user->initials();
?>

<nav aria-label="Fil d'Ariane" class="mb-2">
    <small class="breadcrumb d-flex gap-2">
        <a href="<?= e(url('/admin')) ?>">Tableau de bord</a>
        <a href="<?= e(url('/admin/clients')) ?>">Clients</a>
        <span class="text-muted-2"><?= e($user->fullName()) ?></span>
    </small>
</nav>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="table-card text-center">
            <?php if (!empty($user->avatar)): ?>
                <img src="<?= e(img($user->avatar)) ?>" alt="" class="rounded-circle mb-2"
                     width="88" height="88" style="object-fit:cover">
            <?php else: ?>
                <span class="badge rounded-circle bg-primary d-inline-flex align-items-center justify-content-center mb-2"
                      style="width:88px;height:88px;font-size:1.7rem"><?= e($initials) ?></span>
            <?php endif; ?>

            <h2 class="h5 fw-bold mb-0"><?= e($user->fullName()) ?></h2>
            <p class="text-muted-2 small mb-2">@<?= e($user->username) ?></p>

            <div class="d-flex justify-content-center gap-2 mb-3">
                <span class="badge <?= $user->isAdmin() ? 'bg-dark' : 'bg-light text-dark' ?>">
                    <?= $user->isAdmin() ? 'Administrateur' : 'Client' ?>
                </span>
                <span class="badge bg-<?= $user->isActive() ? 'success' : 'secondary' ?>">
                    <?= $user->isActive() ? 'Compte actif' : 'Compte bloqué' ?>
                </span>
            </div>

            <div class="row g-2 text-start small">
                <div class="col-5 text-muted-2">E-mail</div>
                <div class="col-7 fw-semibold text-break"><?= e($user->email) ?></div>

                <div class="col-5 text-muted-2">Téléphone</div>
                <div class="col-7"><?= e($user->phone ?: '—') ?></div>

                <div class="col-5 text-muted-2">Prénom</div>
                <div class="col-7"><?= e($user->first_name ?: '—') ?></div>

                <div class="col-5 text-muted-2">Nom</div>
                <div class="col-7"><?= e($user->last_name ?: '—') ?></div>

                <div class="col-5 text-muted-2">Inscription</div>
                <div class="col-7"><?= e(format_date($user->created_at)) ?></div>

                <div class="col-5 text-muted-2">Dernière connexion</div>
                <div class="col-7"><?= e(format_date($user->last_login_at)) ?></div>
            </div>
        </div>

        <div class="row g-3 mt-0">
            <div class="col-6">
                <div class="stat-card">
                    <div class="icon"><i class="bi bi-bag"></i></div>
                    <div class="value"><?= (int) ($stats['orders'] ?? 0) ?></div>
                    <div class="label">Commandes</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="icon"><i class="bi bi-cash-stack"></i></div>
                    <div class="value"><?= e(money($stats['spent'] ?? 0)) ?></div>
                    <div class="label">Total dépensé</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Commandes du client</h2>

            <?php if ($orders === []): ?>
                <p class="text-muted-2 small mb-0">Ce client n'a encore passé aucune commande.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>N° commande</th>
                                <th>Date</th>
                                <th class="text-center">Articles</th>
                                <th class="text-center">Paiement</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a class="fw-semibold" href="<?= e(url('/admin/commandes/' . (int) $order->id)) ?>">
                                        <?= e($order->order_number) ?>
                                    </a>
                                    <?php if ($order->items !== []): ?>
                                        <div class="text-muted-2" style="font-size:.78rem">
                                            <?= e(excerpt(implode(', ', array_map(
                                                static fn ($item): string => (string) $item->product_name,
                                                array_slice($order->items, 0, 2)
                                            )), 60)) ?>
                                            <?= count($order->items) > 2 ? '…' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted-2"><?= e(format_date($order->created_at)) ?></td>
                                <td class="text-center small"><?= count($order->items) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $order->is_paid ? 'success' : 'warning' ?>">
                                        <?= $order->is_paid ? 'Payé' : 'En attente' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= e($order->statusBadge()) ?>">
                                        <?= e($order->statusLabel()) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-semibold"><?= e(money($order->total)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>