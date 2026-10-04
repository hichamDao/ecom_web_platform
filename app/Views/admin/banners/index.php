<?php
/**
 * Back-office : bannières promotionnelles de la page d'accueil.
 *
 * @var array<int, \App\Models\PromoBanner> $banners
 */
$banners = $banners ?? [];

$editing = null;
if (isset($_GET['modifier'])) {
    foreach ($banners as $banner) {
        if ((int) $banner->id === (int) $_GET['modifier']) {
            $editing = $banner;
        }
    }
}
?>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Bannières (<?= count($banners) ?>)</h2>

            <?php if ($banners === []): ?>
                <p class="text-muted-2 small mb-0">Aucune bannière. Ajoutez la première à droite.</p>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($banners as $banner): ?>
                        <div class="list-group-item px-0 d-flex gap-3 align-items-center">
                            <img src="<?= e(img($banner->photo)) ?>" alt="" width="110" height="64"
                                 class="rounded" style="object-fit:cover;background:#eef2ff">

                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-light text-dark border">#<?= (int) $banner->position ?></span>
                                    <strong class="text-truncate"><?= e($banner->title) ?></strong>
                                    <?php if (!$banner->is_active): ?>
                                        <span class="badge bg-secondary">Masquée</span>
                                    <?php endif; ?>
                                </div>
                                <div class="small text-muted-2 text-truncate">
                                    <?= e(excerpt($banner->subtitle, 70)) ?>
                                </div>
                                <div class="small text-muted-2">
                                    <?= e($banner->button_text ?: 'Découvrir') ?>
                                    <?php if (!empty($banner->link)): ?>
                                        · <span class="text-truncate"><?= e($banner->link) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-inline-flex gap-1">
                                <a class="btn btn-sm btn-outline-primary"
                                   href="<?= e(url('/admin/bannieres?modifier=' . (int) $banner->id)) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post"
                                      action="<?= e(url('/admin/bannieres/' . (int) $banner->id . '/supprimer')) ?>"
                                      onsubmit="return confirm('Supprimer cette bannière ?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">
                <?= $editing !== null ? 'Modifier la bannière' : 'Nouvelle bannière' ?>
            </h2>

            <form method="post" enctype="multipart/form-data"
                  action="<?= e($editing !== null ? url('/admin/bannieres/' . (int) $editing->id) : url('/admin/bannieres')) ?>">
                <?= csrf_field() ?>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="title">Titre *</label>
                    <input type="text" class="form-control form-control-sm" id="title" name="title" required
                           maxlength="160" value="<?= e($editing?->title ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="subtitle">Sous-titre</label>
                    <textarea class="form-control form-control-sm" id="subtitle" name="subtitle"
                              rows="2"><?= e($editing?->subtitle ?? '') ?></textarea>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label small fw-semibold" for="buttonText">Texte du bouton</label>
                        <input type="text" class="form-control form-control-sm" id="buttonText" name="buttonText"
                               value="<?= e($editing?->button_text ?? 'Découvrir') ?>">
                    </div>
                    <div class="col-5">
                        <label class="form-label small fw-semibold" for="position">Position</label>
                        <input type="number" class="form-control form-control-sm" id="position" name="position"
                               min="0" placeholder="Auto"
                               value="<?= e($editing?->position ?? '') ?>">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="link">Lien (facultatif)</label>
                    <input type="text" class="form-control form-control-sm" id="link" name="link"
                           placeholder="/boutique?categorie=2 ou https://…"
                           value="<?= e($editing?->link ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="colorHex">Couleur de fond (#RRGGBB)</label>
                    <input type="text" class="form-control form-control-sm" id="colorHex" name="colorHex"
                           placeholder="#4f46e5" value="<?= e($editing?->color_hex ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="photo">Image (JPEG, PNG, WebP — 4 Mo max)</label>
                    <input type="file" class="form-control form-control-sm" id="photo" name="photo"
                           accept="image/jpeg,image/png,image/webp">
                    <?php if ($editing !== null && !empty($editing->photo)): ?>
                        <div class="form-text">Image actuelle : <code><?= e($editing->photo) ?></code></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input type="hidden" name="isActive" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="isActive"
                               name="isActive" value="1"
                            <?= checked($editing === null ? true : (bool) $editing->is_active) ?>>
                        <label class="form-check-label small" for="isActive">Bannière visible sur le site</label>
                    </div>
                    <div class="form-text">
                        Laissez le fichier vide pour conserver l'image actuelle.
                    </div>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                    <?= $editing !== null ? 'Enregistrer' : 'Créer la bannière' ?>
                </button>
                <?php if ($editing !== null): ?>
                    <a class="btn btn-link w-100 mt-1" href="<?= e(url('/admin/bannieres')) ?>">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>