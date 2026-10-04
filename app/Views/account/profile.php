<?php
/**
 * Espace client : profil, résumé, raccourcis.
 */
$user = $user ?? auth();
$orders = $orders ?? [];
$addresses = $addresses ?? [];
$wishlist = $wishlist ?? 0;
$reviews = $reviews ?? [];
$notifications = $notifications ?? [];
$stats = $stats ?? ['orders' => 0, 'spent' => 0];
?>

<div class="account-header">
    <div class="d-flex align-items-center gap-3">
        <span class="badge rounded-circle bg-primary text-white" style="width:56px;height:56px;display:inline-flex;align-items:center;justify-content:center;font-size:1.3rem">
            <?= e($user->initials()) ?>
        </span>
        <div>
            <h1 class="h4 fw-bold mb-0">Bonjour <?= e($user->first_name ?: $user->username) ?></h1>
            <small class="text-muted-2">Client depuis le <?= e(format_date($user->created_at, 'd/m/Y')) ?></small>
        </div>
    </div>

    <nav class="nav nav-pills gap-1">
        <a class="nav-link <?= is_active('/compte') ?>" href="<?= e(url('/compte')) ?>">Profil</a>
        <a class="nav-link <?= is_active('/compte/commandes') ?>" href="<?= e(url('/compte/commandes')) ?>">Commandes</a>
        <a class="nav-link <?= is_active('/compte/favoris') ?>" href="<?= e(url('/compte/favoris')) ?>">Favoris</a>
        <a class="nav-link <?= is_active('/compte/adresses') ?>" href="<?= e(url('/compte/adresses')) ?>">Adresses</a>
    </nav>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="icon" style="background:var(--gold)"><i class="bi bi-bag"></i></span>
        <div><div class="value"><?= (int) $stats['orders'] ?></div><div class="label">Commandes</div></div>
    </div>
    <div class="stat-card">
        <span class="icon" style="background:#059669"><i class="bi bi-cash-stack"></i></span>
        <div><div class="value"><?= e(money($stats['spent'])) ?></div><div class="label">Total dépensé</div></div>
    </div>
    <div class="stat-card">
        <span class="icon" style="background:#e11d48"><i class="bi bi-heart"></i></span>
        <div><div class="value"><?= (int) $wishlist ?></div><div class="label">Favoris</div></div>
    </div>
    <div class="stat-card">
        <span class="icon" style="background:#0ea5e9"><i class="bi bi-geo-alt"></i></span>
        <div><div class="value"><?= count($addresses) ?></div><div class="label">Adresses</div></div>
    </div>
</div>

<div class="account-layout">
    <div class="account-main">
        <div class="panel mb-4">
            <h2 class="panel-title"><i class="bi bi-person-gear me-1"></i>Mes informations</h2>
            <form method="post" action="<?= e(url('/compte')) ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="username">Nom d'utilisateur</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?= e($user->username) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="email">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($user->email) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="first_name">Prénom</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?= e($user->first_name) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="last_name">Nom</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?= e($user->last_name) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="phone">Téléphone</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= e($user->phone) ?>">
                    </div>
                </div>
                <button class="btn btn-primary btn-sm mt-3" type="submit">Enregistrer</button>
            </form>
        </div>

        <div class="panel">
            <h2 class="panel-title"><i class="bi bi-key me-1"></i>Changer mon mot de passe</h2>
            <form method="post" action="<?= e(url('/compte/mot-de-passe')) ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="old_password">Ancien</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="new_password">Nouveau</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="6" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="new_password_confirmation">Confirmation</label>
                        <input type="password" class="form-control" id="new_password_confirmation"
                               name="new_password_confirmation" minlength="6" required>
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm mt-3" type="submit">Mettre à jour</button>
            </form>
        </div>
    </div>

    <div class="account-sidebar">
        <div class="panel mb-4">
            <div class="panel-title"><i class="bi bi-receipt me-1"></i>Dernières commandes</div>
            <?php if ($orders === []): ?>
                <p class="text-muted-2 small mb-0">Aucune commande pour le moment.</p>
            <?php else: ?>
                <?php foreach (array_slice($orders, 0, 4) as $order): ?>
                    <a class="order-link" href="<?= e(url('/compte/commandes/' . (int) $order->id)) ?>">
                        <span class="small">
                            <strong><?= e($order->order_number) ?></strong><br>
                            <span class="text-muted-2"><?= e(format_date($order->created_at, 'd/m/Y')) ?></span>
                        </span>
                        <span class="text-end">
                            <span class="d-block small fw-semibold"><?= e(money($order->total)) ?></span>
                            <span class="badge badge-<?= e($order->statusBadge()) ?>"><?= e($order->statusLabel()) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-title"><i class="bi bi-bell me-1"></i>Notifications récentes</div>
            <?php if ($notifications === []): ?>
                <p class="text-muted-2 small mb-0">Aucune notification.</p>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item">
                        <div class="fw-semibold">
                            <?= e($notification->title) ?>
                            <?php if (!$notification->is_read): ?>
                                <span class="badge badge-sale ms-1">Nouveau</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted-2"><?= e($notification->message) ?></div>
                        <small class="text-muted-2"><?= e(format_date($notification->created_at, 'd/m/Y H:i')) ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>