<?php
/**
 * Carnet d'adresses du client.
 */
$addresses = $addresses ?? [];
?>

<h1 class="h4 fw-bold mb-4">Mes adresses</h1>

<div class="addresses-layout">
    <div class="addresses-list">
        <?php if ($addresses === []): ?>
            <div class="panel text-center py-5">
                <i class="bi bi-geo-alt fs-1 text-muted-2"></i>
                <p class="mt-3 mb-0 text-muted-2">Aucune adresse enregistrée.</p>
            </div>
        <?php else: ?>
            <?php foreach ($addresses as $address): ?>
                <div class="address-card">
                    <div class="address-header">
                        <strong><?= e($address->full_name) ?></strong>
                        <?php if ($address->is_default): ?>
                            <span class="badge badge-soft">Par défaut</span>
                        <?php endif; ?>
                    </div>
                    <div class="address-body">
                        <?= e($address->formatted()) ?><br>
                        <?= e($address->phone) ?>
                    </div>
                    <div class="address-actions">
                        <?php if (!$address->is_default): ?>
                            <form method="post" action="<?= e(url('/compte/adresses/' . (int) $address->id . '/defaut')) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-primary" type="submit">Définir par défaut</button>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url('/compte/adresses/' . (int) $address->id . '/supprimer')) ?>"
                              onsubmit="return confirm('Supprimer cette adresse ?');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>

                        <button class="btn btn-sm btn-link p-0 text-decoration-none"
                                type="button" onclick="toggleAddressForm(<?= (int) $address->id ?>)">
                            Modifier
                        </button>
                    </div>

                    <div class="address-edit" id="modif<?= (int) $address->id ?>">
                        <form method="post" action="<?= e(url('/compte/adresses/' . (int) $address->id)) ?>">
                            <?= csrf_field() ?>
                            <input type="text" class="form-control form-control-sm mb-1" name="full_name"
                                   value="<?= e($address->full_name) ?>" required placeholder="Nom complet">
                            <input type="text" class="form-control form-control-sm mb-1" name="phone"
                                   value="<?= e($address->phone) ?>" required placeholder="Téléphone">
                            <input type="text" class="form-control form-control-sm mb-1" name="address"
                                   value="<?= e($address->address) ?>" required placeholder="Adresse">
                            <div class="row g-1">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm mb-1" name="city"
                                           value="<?= e($address->city) ?>" required placeholder="Ville">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm mb-1" name="state"
                                           value="<?= e($address->state) ?>" placeholder="Région">
                                </div>
                            </div>
                            <button class="btn btn-sm btn-primary w-100 mt-2" type="submit">Enregistrer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="address-form-sidebar">
        <div class="panel">
            <h2 class="panel-title">Ajouter une adresse</h2>
            <form method="post" action="<?= e(url('/compte/adresses')) ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="full_name">Nom complet *</label>
                    <input type="text" class="form-control form-control-sm<?= old_has('full_name') ?>" id="full_name"
                           name="full_name" value="<?= e(old('full_name', auth()?->fullName())) ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="phone">Téléphone *</label>
                    <input type="text" class="form-control form-control-sm" id="phone" name="phone"
                           value="<?= e(old('phone', auth()?->phone)) ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="address">Adresse *</label>
                    <input type="text" class="form-control form-control-sm" id="address" name="address"
                           value="<?= e(old('address')) ?>" required>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="city">Ville *</label>
                        <input type="text" class="form-control form-control-sm" id="city" name="city"
                               value="<?= e(old('city')) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="state">Région</label>
                        <input type="text" class="form-control form-control-sm" id="state" name="state"
                               value="<?= e(old('state')) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="zip_code">Code postal</label>
                        <input type="text" class="form-control form-control-sm" id="zip_code" name="zip_code"
                               value="<?= e(old('zip_code')) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="country">Pays *</label>
                        <input type="text" class="form-control form-control-sm" id="country" name="country"
                               value="<?= e(old('country', 'Maroc')) ?>" required>
                    </div>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="isDefault" value="1" id="isDefault">
                    <label class="form-check-label small" for="isDefault">Définir comme adresse par défaut</label>
                </div>
                <button class="btn btn-primary w-100 mt-3" type="submit">Enregistrer l'adresse</button>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAddressForm(id) {
    const el = document.getElementById('modif' + id);
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>