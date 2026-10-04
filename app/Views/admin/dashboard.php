<?php
/**
 * Tableau de bord : indicateurs, graphique du CA, dernières commandes.
 */
$stats = $stats ?? [];
$monthly = $monthly ?? [];
$byCategory = $byCategory ?? [];
$topProducts = $topProducts ?? [];
$recentOrders = $recentOrders ?? [];
$lowStock = $lowStock ?? [];
$pendingReviews = $pendingReviews ?? [];
$statusCounts = $statusCounts ?? [];

$maxRevenue = 1.0;
foreach ($monthly as $month) {
    $maxRevenue = max($maxRevenue, (float) $month['totalSales']);
}

$icons = [
    ['#4f46e5', 'bi-cash-coin', money($stats['revenue'] ?? 0), 'Chiffre d\'affaires'],
    ['#0ea5e9', 'bi-bag-check', (int) ($stats['orderCount'] ?? 0), 'Commandes'],
    ['#059669', 'bi-people', (int) ($stats['customerCount'] ?? 0), 'Clients'],
    ['#f59e0b', 'bi-box-seam', (int) ($stats['productCount'] ?? 0), 'Produits'],
];
?>

<div class="row g-3 mb-4">
    <?php foreach ($icons as [$color, $icon, $value, $label]): ?>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <span class="icon" style="background:<?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></span>
                <div>
                    <div class="value"><?= e((string) $value) ?></div>
                    <div class="label"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
    <?php
    $extra = [
        ['#7c3aed', 'bi-graph-up', money($stats['averageOrder'] ?? 0), 'Panier moyen'],
        ['#e11d48', 'bi-box-arrow-down', number_format((int) ($stats['unitsSold'] ?? 0)), 'Articles vendus'],
        ['#0d9488', 'bi-hourglass-split', (int) ($stats['pendingOrders'] ?? 0), 'Commandes en attente'],
        ['#be123c', 'bi-star', (int) ($stats['pendingReviews'] ?? 0), 'Avis à modérer'],
    ];
    foreach ($extra as [$color, $icon, $value, $label]): ?>
        <div class="col-md-6 col-xl-3">
            <div class="stat-card">
                <span class="icon" style="background:<?= e($color) ?>"><i class="bi <?= e($icon) ?>"></i></span>
                <div>
                    <div class="value"><?= e((string) $value) ?></div>
                    <div class="label"><?= e($label) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Chiffre d'affaires des 12 derniers mois</h2>
            <div class="bar-chart">
                <?php foreach ($monthly as $month): ?>
                    <?php $ratio = (float) $month['totalSales'] / $maxRevenue * 100; ?>
                    <div class="bar" title="<?= e($month['month'] . ' ' . $month['year'] . ' : ' . money($month['totalSales'])) ?>">
                        <span style="height:<?= max(2, round($ratio)) ?>%"></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="labels">
                <?php foreach ($monthly as $month): ?>
                    <span><?= e($month['month']) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Dernières commandes</h2>
            <?php if ($recentOrders === []): ?>
                <p class="text-muted-2 small mb-0">Aucune commande.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Commande</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Statut</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($order->order_number) ?></td>
                                <td class="small"><?= e($order->customerName()) ?></td>
                                <td class="small text-muted-2"><?= e(format_date($order->created_at, 'd/m/Y')) ?></td>
                                <td class="text-end fw-semibold"><?= e(money($order->total)) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= e($order->statusBadge()) ?>"><?= e($order->statusLabel()) ?></span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="<?= e(url('/admin/commandes/' . (int) $order->id)) ?>">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Ventes par catégorie</h2>
            <?php if ($byCategory === []): ?>
                <p class="text-muted-2 small mb-0">Pas encore de données.</p>
            <?php else: ?>
                <?php
                $maxCategory = 1.0;
                foreach ($byCategory as $row) {
                    $maxCategory = max($maxCategory, (float) $row['totalRevenue']);
                }
                ?>
                <?php foreach (array_slice($byCategory, 0, 6) as $row): ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= e($row['categoryName']) ?></span>
                            <span class="text-muted-2"><?= e(money($row['totalRevenue'])) ?></span>
                        </div>
                        <div class="progress" style="height:8px">
                            <div class="progress-bar" style="width:<?= round((float) $row['totalRevenue'] / $maxCategory * 100) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Meilleures ventes</h2>
            <?php if ($topProducts === []): ?>
                <p class="text-muted-2 small mb-0">Pas encore de données.</p>
            <?php else: ?>
                <?php foreach ($topProducts as $row): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <span><?= e(excerpt($row['productName'], 30)) ?></span>
                        <span class="text-muted-2"><?= (int) $row['quantitySold'] ?> vendu(s)</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="table-card mb-3">
            <h2 class="h6 fw-bold mb-3">Stock faible</h2>
            <?php if ($lowStock === []): ?>
                <p class="text-muted-2 small mb-0">Aucun produit en stock faible.</p>
            <?php else: ?>
                <?php foreach ($lowStock as $product): ?>
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                        <a class="text-decoration-none text-body" href="<?= e(url('/admin/produits/' . (int) $product->id . '/modifier')) ?>">
                            <?= e(excerpt($product->name, 28)) ?>
                        </a>
                        <span class="badge bg-<?= (int) $product->stock === 0 ? 'danger' : 'warning' ?>">
                            <?= (int) $product->stock ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Statuts des commandes</h2>
            <?php foreach ($statusCounts as $row): ?>
                <div class="d-flex justify-content-between align-items-center py-1 small">
                    <span><span class="badge bg-<?= e(order_status_badge($row['status'])) ?>"><?= e(order_status_label($row['status'])) ?></span></span>
                    <span class="fw-semibold"><?= (int) $row['count'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>