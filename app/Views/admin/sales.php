<?php
/**
 * Rapports : CA mensuel, ventes par catégorie, top produits, meilleurs clients.
 */
$stats = $stats ?? [];
$monthly = $monthly ?? [];
$byCategory = $byCategory ?? [];
$topProducts = $topProducts ?? [];
$customers = $customers ?? [];
$statusCounts = $statusCounts ?? [];
$bestSeller = $bestSeller ?? null;

$maxRevenue = 1.0;
foreach ($monthly as $month) {
    $maxRevenue = max($maxRevenue, (float) $month['totalSales']);
}
?>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="icon" style="background:#4f46e5"><i class="bi bi-cash-coin"></i></span>
            <div><div class="value"><?= e(money($stats['revenue'] ?? 0)) ?></div><div class="label">CA total (payé)</div></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="icon" style="background:#0ea5e9"><i class="bi bi-graph-up"></i></span>
            <div><div class="value"><?= e(money($stats['averageOrder'] ?? 0)) ?></div><div class="label">Panier moyen</div></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="icon" style="background:#059669"><i class="bi bi-bag-check"></i></span>
            <div><div class="value"><?= (int) ($stats['unitsSold'] ?? 0) ?></div><div class="label">Articles vendus</div></div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card">
            <span class="icon" style="background:#f59e0b"><i class="bi bi-trophy"></i></span>
            <div>
                <div class="value" style="font-size:1rem"><?= e($bestSeller ? excerpt($bestSeller->name, 22) : '—') ?></div>
                <div class="label">Meilleure vente</div>
            </div>
        </div>
    </div>
</div>

<div class="table-card mb-3">
    <h2 class="h6 fw-bold mb-3">Chiffre d'affaires mensuel</h2>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Mois</th>
                    <th class="text-end">Commandes</th>
                    <th style="width:45%">CA</th>
                    <th class="text-end">Montant</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($monthly as $month): ?>
                <tr>
                    <td><?= e($month['month'] . ' ' . $month['year']) ?></td>
                    <td class="text-end"><?= (int) $month['orderCount'] ?></td>
                    <td>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar" style="width:<?= round((float) $month['totalSales'] / $maxRevenue * 100) ?>%"></div>
                        </div>
                    </td>
                    <td class="text-end fw-semibold"><?= e(money($month['totalSales'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Ventes par catégorie</h2>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Catégorie</th><th class="text-end">Quantité</th><th class="text-end">CA</th></tr></thead>
                    <tbody>
                    <?php foreach ($byCategory as $row): ?>
                        <tr>
                            <td><?= e($row['categoryName']) ?></td>
                            <td class="text-end"><?= (int) $row['totalQuantity'] ?></td>
                            <td class="text-end fw-semibold"><?= e(money($row['totalRevenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($byCategory === []): ?>
                        <tr><td colspan="3" class="text-muted-2 small">Pas encore de données.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Top produits</h2>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Produit</th><th class="text-end">Vendu</th><th class="text-end">CA</th></tr></thead>
                    <tbody>
                    <?php foreach ($topProducts as $row): ?>
                        <tr>
                            <td><?= e(excerpt($row['productName'], 34)) ?></td>
                            <td class="text-end"><?= (int) $row['quantitySold'] ?></td>
                            <td class="text-end fw-semibold"><?= e(money($row['totalRevenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($topProducts === []): ?>
                        <tr><td colspan="3" class="text-muted-2 small">Pas encore de données.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Meilleurs clients</h2>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>E-mail</th>
                            <th class="text-end">Commandes</th>
                            <th class="text-end">Total dépensé</th>
                            <th class="text-end">Dernière commande</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td>
                                <?php if (!empty($customer['userId'])): ?>
                                    <a href="<?= e(url('/admin/clients/' . (int) $customer['userId'])) ?>"><?= e($customer['name'] ?? 'Client') ?></a>
                                <?php else: ?>
                                    <?= e($customer['name'] ?? 'Client invité') ?>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted-2"><?= e($customer['email']) ?></td>
                            <td class="text-end"><?= (int) $customer['totalOrders'] ?></td>
                            <td class="text-end fw-semibold"><?= e(money($customer['totalSpent'])) ?></td>
                            <td class="text-end small text-muted-2"><?= e(format_date($customer['lastOrderDate'], 'd/m/Y')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($customers === []): ?>
                        <tr><td colspan="5" class="text-muted-2 small">Pas encore de données.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>