<?php
/**
 * Back-office : arborescence des catégories.
 */
$categories = $categories ?? [];
$counts = $counts ?? [];
$editing = null;
if (isset($_GET['modifier'])) {
    foreach ($categories as $category) {
        if ((int) $category->id === (int) $_GET['modifier']) {
            $editing = $category;
        }
    }
}
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Catégories</h2>

            <?php if ($categories === []): ?>
                <p class="text-muted-2 small mb-0">Aucune catégorie. Créez la première à droite.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Catégorie</th><th>Parent</th><th class="text-center">Produits</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $category): ?>
                            <?php $depth = $category->parent_id === null ? 0 : 1; ?>
                            <tr>
                                <td style="padding-left:<?= $depth ? 2 : 1 ?>rem">
                                    <?php if ($depth): ?><i class="bi bi-arrow-return-right text-muted-2 me-1"></i><?php endif; ?>
                                    <img src="<?= e(img($category->image)) ?>" alt="" width="34" height="34"
                                         class="rounded me-2" style="object-fit:cover">
                                    <strong><?= e($category->name) ?></strong>
                                </td>
                                <td class="small text-muted-2">
                                    <?php
                                    if ($category->parent_id === null) {
                                        echo '—';
                                    } else {
                                        foreach ($categories as $parent) {
                                            if ((int) $parent->id === (int) $category->parent_id) {
                                                echo e($parent->name);
                                            }
                                        }
                                    }
                                    ?>
                                </td>
                                <td class="text-center"><?= (int) ($counts[(int) $category->id] ?? 0) ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= e(url('/admin/categories?modifier=' . (int) $category->id)) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post" action="<?= e(url('/admin/categories/' . (int) $category->id . '/supprimer')) ?>"
                                              onsubmit="return confirm('Supprimer cette catégorie ?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3"><?= $editing !== null ? 'Modifier la catégorie' : 'Nouvelle catégorie' ?></h2>

            <form method="post"
                  action="<?= e($editing !== null ? url('/admin/categories/' . (int) $editing->id) : url('/admin/categories')) ?>">
                <?= csrf_field() ?>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="name">Nom *</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= e($editing?->name ?? '') ?>">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($editing?->description ?? '') ?></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="image">Image (URL)</label>
                    <input type="text" class="form-control" id="image" name="image" value="<?= e($editing?->image ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="parent_id">Catégorie parente</label>
                    <select class="form-select" id="parent_id" name="parent_id">
                        <option value="">— Aucune (catégorie principale) —</option>
                        <?php foreach ($categories as $category): ?>
                            <?php if ($editing !== null && (int) $category->id === (int) $editing->id) continue; ?>
                            <option value="<?= (int) $category->id ?>"
                                <?= selected($editing?->parent_id ?? '', $category->id) ?>><?= e($category->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary w-100" type="submit">
                    <?= $editing !== null ? 'Enregistrer' : 'Créer la catégorie' ?>
                </button>
                <?php if ($editing !== null): ?>
                    <a class="btn btn-link w-100 mt-1" href="<?= e(url('/admin/categories')) ?>">Annuler la modification</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>