<?php
/**
 * Back-office : liste des clients et meilleurs acheteurs.
 *
 * @var \App\Core\Paginator $paginator
 * @var array<int, array<string, mixed>> $stats
 * @var array<string, int> $totals
 */
use App\Core\View;

$users = $paginator->items;
?>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-people"></i></div>
            <div class="value"><?= (int) ($totals['customers'] ?? 0) ?></div>
            <div class="label">Comptes clients</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-person-badge"></i></div>
            <div class="value"><?= $paginator->total ?></div>
            <div class="label">Résultats (mots-clés inclus)</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-bag-check"></i></div>
            <div class="value"><?= (int) ($totals['orders'] ?? 0) ?></div>
            <div class="label">Commandes au total</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="value">
                <?= money(array_sum(array_map(static fn (array $s): float => (float) $s['totalSpent'], $stats))) ?>
            </div>
            <div class="label">CA des <?= count($stats) ?> meilleurs clients</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <form method="get" class="d-flex gap-2">
                <input type="search" name="q" class="form-control form-control-sm" style="width:260px"
                       placeholder="Nom, e-mail ou identifiant"
                       value="<?= e($keyword ?? '') ?>">
                <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <?php if (($keyword ?? '') !== ''): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/clients')) ?>">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </form>
            <span class="small text-muted-2">Administrateurs inclus dans la liste</span>
        </div>

        <div class="table-card">
            <?php if ($users === []): ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-x fs-1 text-muted-2"></i>
                    <p class="mt-3 mb-0 text-muted-2">Aucun client ne correspond à cette recherche.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Contact</th>
                                <th class="text-center">Rôle</th>
                                <th class="text-center">Statut</th>
                                <th class="text-end">Inscrit le</th>
                                <th class="text-end">Fiche</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($user->avatar)): ?>
                                            <img src="<?= e(img($user->avatar)) ?>" alt="" class="rounded-circle"
                                                 width="40" height="40" style="object-fit:cover">
                                        <?php else: ?>
                                            <span class="badge rounded-circle bg-primary d-inline-flex align-items-center
                                                         justify-content-center" style="width:40px;height:40px">
                                                <?= e($user->initials()) ?>
                                            </span>
                                        <?php endif; ?>
                                        <div>
                                            <div class="fw-semibold"><?= e($user->fullName()) ?></div>
                                            <small class="text-muted-2">@<?= e($user->username) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">
                                    <div><?= e($user->email) ?></div>
                                    <?php if (!empty($user->phone)): ?>
                                        <div class="text-muted-2"><?= e($user->phone) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $user->isAdmin() ? 'bg-dark' : 'bg-light text-dark' ?>">
                                        <?= $user->isAdmin() ? 'Admin' : 'Client' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $user->isActive() ? 'success' : 'secondary' ?>">
                                        <?= $user->isActive() ? 'Actif' : 'Bloqué' ?>
                                    </span>
                                </td>
                                <td class="text-end small text-muted-2"><?= e(format_date($user->created_at)) ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?= e(url('/admin/clients/' . (int) $user->id)) ?>">
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
    </div>

    <div class="col-xl-4">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-1">Meilleurs acheteurs</h2>
            <p class="small text-muted-2 mb-3">Classement calculé sur les commandes payées.</p>

            <?php if ($stats === []): ?>
                <p class="small text-muted-2 mb-0">Aucune commande enregistrée pour le moment.</p>
            <?php else: ?>
                <ol class="list-unstyled mb-0">
                    <?php foreach ($stats as $row): ?>
                        <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
                            <div class="min-w-0">
                                <div class="fw-semibold small text-truncate">
                                    <?= e($row['name'] !== null && $row['name'] !== '' ? $row['name'] : 'Client invité') ?>
                                </div>
                                <div class="text-muted-2" style="font-size:.78rem">
                                    <?= (int) $row['totalOrders'] ?> commande(s)
                                    <?php if ($row['userId'] !== null): ?>
                                        · <a href="<?= e(url('/admin/clients/' . (int) $row['userId'])) ?>">fiche</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold small"><?= e(money($row['totalSpent'])) ?></div>
                                <div class="text-muted-2" style="font-size:.75rem">
                                    <?= e(format_date($row['lastOrderDate'])) ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</div>