<?php
/**
 * Layout du back-office : barre latérale + zone de contenu.
 * @var string $content
 */
use App\Core\View;

$user = $currentUser ?? \App\Core\Auth::user();

$menu = [
    ['/admin', 'bi-speedometer2', 'Tableau de bord'],
    ['/admin/statistiques', 'bi-graph-up-arrow', 'Statistiques'],
    ['/admin/produits', 'bi-box-seam', 'Produits'],
    ['/admin/categories', 'bi-tags', 'Catégories'],
    ['/admin/commandes', 'bi-bag-check', 'Commandes'],
    ['/admin/clients', 'bi-people', 'Clients'],
    ['/admin/avis', 'bi-star', 'Avis'],
    ['/admin/codes-promo', 'bi-percent', 'Codes promo'],
    ['/admin/bannieres', 'bi-images', 'Bannières'],
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Back-office') ?> · <?= e(config('app.name', 'EcomWeb')) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/placeholder.svg')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="brand-mark">Ecom<span>Web</span></div>

        <div class="group-title">Navigation</div>
        <?php foreach ($menu as [$path, $icon, $label]): ?>
            <a class="<?= route_is($path) ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                <i class="bi <?= e($icon) ?>"></i><?= e($label) ?>
            </a>
        <?php endforeach; ?>

        <div class="group-title">Boutique</div>
        <a href="<?= e(url('/')) ?>"><i class="bi bi-shop"></i>Voir la vitrine</a>
        <a href="<?= e(url('/compte')) ?>"><i class="bi bi-person-circle"></i>Mon profil</a>

        <div class="group-title">Session</div>
        <form method="post" action="<?= e(url('/deconnexion')) ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-light w-100" type="submit">
                <i class="bi bi-box-arrow-right me-1"></i>Déconnexion
            </button>
        </form>
    </aside>

    <main class="admin-main">
        <header class="admin-header">
            <div>
                <h1><?= e($title ?? 'Back-office') ?></h1>
                <?php if (!empty($subtitle)): ?>
                    <p class="text-muted-2 mb-0 small"><?= e($subtitle) ?></p>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <?php if ($user !== null): ?>
                    <span class="text-muted-2 small">
                        Connecté en tant que <strong><?= e($user->fullName()) ?></strong>
                    </span>
                    <span class="badge bg-primary text-uppercase"><?= e($user->role) ?></span>
                <?php endif; ?>
            </div>
        </header>

        <?= View::capture('partials.flash') ?>
        <?= $content ?>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>