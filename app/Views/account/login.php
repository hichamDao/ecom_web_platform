<?php
/**
 * Connexion (boutique + back-office).
 */
?>
<div class="login-wrap">
    <div class="row g-0 shadow-sm">
        <div class="col-md-5 d-none d-md-block" style="background:linear-gradient(135deg,var(--gold),#ad802a)">
            <div class="text-white p-4 h-100 d-flex flex-column justify-content-center">
                <h2 class="fw-bold mb-3">Bienvenue sur <?= e(config('app.name', 'EcomWeb')) ?></h2>
                <p class="mb-4">Connectez-vous pour accéder à vos commandes, vos favoris et vos adresses.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-bag-check me-2"></i>Suivi de vos commandes</li>
                    <li class="mb-2"><i class="bi bi-heart me-2"></i>Liste de favoris</li>
                    <li><i class="bi bi-lightning-charge me-2"></i>Accès aux promotions</li>
                </ul>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card-auth p-4 h-100">
                <h1 class="h4 fw-bold mb-1">Connexion</h1>
                <p class="text-muted-2 small mb-4">Pas encore de compte ?
                    <a href="<?= e(url('/inscription')) ?>">Créer un compte</a>
                </p>

                <form method="post" action="<?= e(url('/connexion')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="email">E-mail</label>
                        <input type="email" class="form-control<?= old_has('email') ?>" id="email" name="email"
                               value="<?= e(old('email')) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="password">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Se connecter</button>
                </form>

                <div class="alert alert-light border small mt-4 mb-0">
                    <div class="fw-semibold mb-1">Comptes de démonstration</div>
                    <div class="text-muted-2">
                        Admin : <code>admin@ecomweb.ma</code> / <code>admin123</code><br>
                        Client : <code>client@ecomweb.ma</code> / <code>password123</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>