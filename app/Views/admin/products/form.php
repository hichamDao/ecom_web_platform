<?php
/**
 * Formulaire de création / modification d'un produit.
 */
$product = $product ?? null;
$categories = $categories ?? [];
$action = $action ?? url('/admin/produits');
$isEdit = $product !== null;
$images = $isEdit && is_array($product->images) ? $product->images : [];
?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="table-card mb-3">
                <h2 class="h6 fw-bold mb-3">Informations</h2>

                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="name">Nom du produit *</label>
                    <input type="text" class="form-control" id="name" name="name" required
                           value="<?= e($isEdit ? $product->name : old('name')) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold" for="description">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="5"><?= e($isEdit ? $product->description : old('description')) ?></textarea>
                </div>

                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="brand">Marque</label>
                        <input type="text" class="form-control" id="brand" name="brand"
                               value="<?= e($isEdit ? $product->brand : old('brand')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="category_id">Catégorie</label>
                        <select class="form-select" id="category_id" name="category_id">
                            <option value="">— Aucune —</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category->id ?>"
                                    <?= selected($isEdit ? $product->category_id : old('category_id'), $category->id) ?>>
                                    <?= e($category->path()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-card mb-3">
                <h2 class="h6 fw-bold mb-3">Prix et stock</h2>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="price">Prix de vente * (MAD)</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required
                               value="<?= e($isEdit ? $product->price : old('price')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="old_price">Prix barré</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="old_price" name="old_price"
                               value="<?= e($isEdit ? $product->old_price : old('old_price')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="stock">Stock *</label>
                        <input type="number" min="0" class="form-control" id="stock" name="stock" required
                               value="<?= e($isEdit ? $product->stock : old('stock', 0)) ?>">
                    </div>
                </div>
                <p class="small text-muted-2 mb-0 mt-2">
                    Le pourcentage de remise est calculé automatiquement à partir du prix barré.
                </p>
            </div>

            <div class="table-card">
                <h2 class="h6 fw-bold mb-3">Images</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="thumbnail">Image principale (URL)</label>
                        <input type="text" class="form-control mb-2" id="thumbnail" name="thumbnail"
                               value="<?= e($isEdit ? $product->thumbnail : old('thumbnail')) ?>"
                               placeholder="https://... ou uploads/produits/xxx.jpg">
                        <input type="file" class="form-control form-control-sm" id="image" name="image" accept="image/*">
                        <?php if ($isEdit && !empty($product->thumbnail)): ?>
                            <img src="<?= e(img($product->thumbnail)) ?>" alt="" class="thumb-sm mt-2">
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="images">Galerie (URL, une par ligne)</label>
                        <textarea class="form-control" id="images" name="images" rows="4"><?= e(implode("\n", $images)) ?></textarea>
                    </div>
                </div>

                <hr>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="sizes">Tailles possibles</label>
                        <input type="text" class="form-control" id="sizes" name="sizes"
                               value="<?= e($isEdit ? implode(', ', (array) ($product->attributes['tailles'] ?? [])) : '') ?>"
                               placeholder="S, M, L, XL">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold" for="colors">Couleurs disponibles</label>
                        <input type="text" class="form-control" id="colors" name="colors"
                               value="<?= e($isEdit ? implode(', ', (array) ($product->attributes['couleurs'] ?? [])) : '') ?>"
                               placeholder="Noir, Blanc, Rouge">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold" for="overlay_image">Image d'essayage virtuel</label>
                        <input type="text" class="form-control" id="overlay_image" name="overlay_image"
                               value="<?= e($isEdit ? $product->overlay_image : old('overlay_image')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="table-card mb-3">
                <h2 class="h6 fw-bold mb-3">Publication</h2>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                        <?= ($isEdit ? $product->is_featured : old('is_featured')) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">Produit en vedette</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_new" value="1" id="is_new"
                        <?= ($isEdit ? $product->is_new : old('is_new')) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_new">Marquer comme nouveau</label>
                </div>
            </div>

            <div class="table-card">
                <div class="d-grid gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'Enregistrer les modifications' : 'Créer le produit' ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/produits')) ?>">Annuler</a>
                </div>
            </div>
        </div>
    </div>
</form>