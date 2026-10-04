<?php
/**
 * Layout principal de la boutique.
 *
 * Variables disponibles : $content (HTML de la vue), $title, $appName,
 * $categories, $currentUser, $cartCount, $flashMessages, ...
 */
use App\Core\View;

$appName = $appName ?? config('app.name', 'EcomWeb');
$flashMessages = $flashMessages ?? [];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? $appName)) ?> · <?= e($appName) ?></title>
    <meta name="description" content="<?= e($appName) ?> — <?= e(config('app.tagline', '')) ?>">
    <link rel="icon" href="<?= e(asset('assets/img/placeholder.svg')) ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>

<div class="top-bar">
    <span><i class="bi bi-truck me-1"></i> Livraison offerte dès <?= e(money($freeShipping ?? free_shipping_from())) ?></span>
    <span>
        <?= e($storeEmail ?? '') ?> · <?= e($storePhone ?? '') ?>
    </span>
</div>

<header class="header">
    <a class="logo" href="<?= e(url('/')) ?>">
        <span class="logo-icon"><i class="bi bi-gem"></i></span>
        <div>
            <h1><?= e($appName) ?></h1>
            <p>Premium Store</p>
        </div>
    </a>

    <form class="search-box" action="<?= e(url('/recherche')) ?>" method="get" role="search">
        <input type="search" name="q"
               placeholder="Rechercher un produit, une marque..."
               value="<?= e(request()->input('q', request()->input('keyword', ''))) ?>">
        <button type="submit"><i class="bi bi-search"></i></button>
    </form>

    <nav class="nav">
        <a class="<?= is_active('/') ? 'active' : '' ?>" href="<?= e(url('/')) ?>">Accueil</a>
        <a class="<?= is_active('/boutique') ? 'active' : '' ?>" href="<?= e(url('/boutique')) ?>">Boutique</a>
        <?php foreach (($categories ?? []) as $category): ?>
            <?php if (!empty($category['children'])): ?>
                <div class="dropdown">
                    <a class="nav-link dropdown-toggle <?= is_active('/categorie/' . $category['id']) ? 'active' : '' ?>"
                       href="<?= e(url('/categorie/' . $category['id'])) ?>"
                       data-bs-toggle="dropdown" role="button">
                        <?= e($category['name']) ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= e(url('/categorie/' . $category['id'])) ?>">Tout voir</a></li>
                        <?php foreach ($category['children'] as $child): ?>
                            <li><a class="dropdown-item" href="<?= e(url('/categorie/' . $child['id'])) ?>"><?= e($child['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <a class="<?= is_active('/categorie/' . $category['id']) ? 'active' : '' ?>" href="<?= e(url('/categorie/' . $category['id'])) ?>"><?= e($category['name']) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!empty($currentUser) && $currentUser->isAdmin()): ?>
            <a class="<?= is_active('/admin') ? 'active' : '' ?>" href="<?= e(url('/admin')) ?>">Back-office</a>
        <?php endif; ?>
    </nav>

    <div class="header-actions">
        <?php if (!empty($currentUser)): ?>
            <div class="dropdown">
                <a class="header-action text-decoration-none" href="#" data-bs-toggle="dropdown">
                    <i class="bi bi-person"></i>
                    <span class="hide-mobile"><?= e($currentUser->fullName()) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= e(url('/compte')) ?>"><i class="bi bi-person me-2"></i>Mon compte</a></li>
                    <li><a class="dropdown-item" href="<?= e(url('/compte/commandes')) ?>"><i class="bi bi-bag me-2"></i>Mes commandes</a></li>
                    <li><a class="dropdown-item" href="<?= e(url('/compte/favoris')) ?>"><i class="bi bi-heart me-2"></i>Mes favoris</a></li>
                    <?php if (!empty($unreadCount)): ?>
                        <li><a class="dropdown-item" href="<?= e(url('/compte')) ?>"><i class="bi bi-bell me-2"></i><?= (int) $unreadCount ?> notification(s)</a></li>
                    <?php endif; ?>
                    <?php if ($currentUser->isAdmin()): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= e(url('/admin')) ?>"><i class="bi bi-speedometer2 me-2"></i>Back-office</a></li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="<?= e(url('/deconnexion')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</button>
                        </form>
                    </li>
                </ul>
            </div>
        <?php else: ?>
            <a class="header-action text-decoration-none" href="<?= e(url('/connexion')) ?>">
                <i class="bi bi-person"></i>
                <span class="hide-mobile">Connexion</span>
            </a>
            <a class="btn btn-sm hide-mobile" href="<?= e(url('/inscription')) ?>" style="background:var(--gold);color:white;padding:6px 14px;border-radius:6px;font-weight:600;">Créer un compte</a>
        <?php endif; ?>

        <a class="header-action text-decoration-none" href="<?= e(url('/panier')) ?>">
            <i class="bi bi-bag"></i>
            <span class="hide-mobile">Panier</span>
            <?php if (($cartCount ?? 0) > 0): ?>
                <span class="cart-count"><?= (int) $cartCount ?></span>
            <?php endif; ?>
        </a>
    </div>
</header>

<main>
    <?= View::capture('partials.flash') ?>
    <?= $content ?>
</main>

<footer class="footer">
    <div class="footer-grid">
        <div>
            <div class="footer-logo"><?= e($appName) ?></div>
            <p style="margin-top:12px;"><?= e(config('app.tagline', '')) ?></p>
            <p style="margin-top:12px;"><i class="bi bi-geo-alt me-1"></i><?= e($storeAddress ?? '') ?></p>
        </div>
        <div>
            <h4>Boutique</h4>
            <p><a href="<?= e(url('/boutique')) ?>">Tous les produits</a></p>
            <p><a href="<?= e(url('/panier')) ?>">Mon panier</a></p>
            <p><a href="<?= e(url('/compte/commandes')) ?>">Suivi de commande</a></p>
        </div>
        <div>
            <h4>Compte</h4>
            <p><a href="<?= e(url('/compte')) ?>">Mon espace</a></p>
            <p><a href="<?= e(url('/compte/adresses')) ?>">Adresses</a></p>
            <p><a href="<?= e(url('/compte/favoris')) ?>">Favoris</a></p>
        </div>
        <div>
            <h4>Contact</h4>
            <p><i class="bi bi-envelope me-1"></i><?= e($storeEmail ?? '') ?></p>
            <p><i class="bi bi-telephone me-1"></i><?= e($storePhone ?? '') ?></p>
            <p><i class="bi bi-clock me-1"></i> Lun – Sam : 9h00 – 19h00</p>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> <?= e($appName) ?> — PHP <?= PHP_VERSION ?> / MySQL</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
<?= View::section('scripts') ?>
</body>
</html>
