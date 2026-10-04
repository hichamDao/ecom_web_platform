<?php
/**
 * Création de compte.
 */
?>
<div class="login-wrap">
    <div class="row g-0 shadow-sm">
        <div class="col-md-5 d-none d-md-block" style="background:linear-gradient(135deg,var(--dark),var(--gold))">
            <div class="text-white p-4 h-100 d-flex flex-column justify-content-center">
                <h2 class="fw-bold mb-3">Rejoignez <?= e(config('app.name', 'EcomWeb')) ?></h2>
                <p class="mb-4">Créez votre compte en 30 secondes et profitez de nos offres exclusives.</p>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><i class="bi bi-percent me-2"></i>Codes promo personnels</li>
                    <li class="mb-2"><i class="bi bi-truck me-2"></i>Livraison suivie</li>
                    <li><i class="bi bi-arrow-counterclockwise me-2"></i>Retours sous 7 jours</li>
                </ul>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card-auth p-4 h-100">
                <h1 class="h4 fw-bold mb-1">Créer un compte</h1>
                <p class="text-muted-2 small mb-4">Déjà client ?
                    <a href="<?= e(url('/connexion')) ?>">Se connecter</a>
                </p>

                <form method="post" action="<?= e(url('/inscription')) ?>">
                    <?= csrf_field() ?>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="first_name">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?= e(old('first_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="last_name">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?= e(old('last_name')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="username">Nom d'utilisateur *</label>
                            <input type="text" class="form-control<?= old_has('username') ?>" id="username"
                                   name="username" value="<?= e(old('username')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="phone">Téléphone</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= e(old('phone')) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" for="email">E-mail *</label>
                            <input type="email" class="form-control<?= old_has('email') ?>" id="email" name="email"
                                   value="<?= e(old('email')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="password">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" for="password_confirmation">Confirmation *</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" minlength="6" required>
                        </div>
                    </div>

                    <button class="btn btn-primary w-100 mt-4" type="submit">Créer mon compte</button>
                </form>
            </div>
        </div>
    </div>
</div>