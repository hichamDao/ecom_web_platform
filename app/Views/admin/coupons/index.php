<?php
/**
 * Back-office : codes promotionnels.
 *
 * @var array<int, \App\Models\Coupon> $coupons
 * @var array<int, array<string, mixed>> $customers
 */
use App\Models\Coupon;

$coupons = $coupons ?? [];
$customers = $customers ?? [];

/** 10.00 => « 10 », 12.50 => « 12,5 ». */
$formatPercentage = static function (float $value): string {
    return rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',');
};

$editing = null;
if (isset($_GET['modifier'])) {
    foreach ($coupons as $coupon) {
        if ((int) $coupon->id === (int) $_GET['modifier']) {
            $editing = $coupon;
        }
    }
}

$restrictedIds = $editing !== null && is_array($editing->product_ids)
    ? array_map('intval', $editing->product_ids)
    : [];
?>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">Codes promo (<?= count($coupons) ?>)</h2>

            <?php if ($coupons === []): ?>
                <p class="text-muted-2 small mb-0">Aucun code promo. Créez le premier à droite.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th class="text-end">Remise</th>
                                <th>Conditions</th>
                                <th class="text-center">Utilisations</th>
                                <th class="text-center">État</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($coupons as $coupon): ?>
                            <?php
                            $label = $coupon->statusLabel();
                            $tone = match ($label) {
                                'Actif'      => 'success',
                                'Expiré'     => 'secondary',
                                'Épuisé'     => 'warning',
                                default      => 'danger',
                            };
                            ?>
                            <tr>
                                <td>
                                    <code class="fw-bold"><?= e($coupon->code) ?></code>
                                    <div class="text-muted-2" style="font-size:.78rem">
                                        Créé le <?= e(format_date($coupon->created_at)) ?>
                                    </div>
                                </td>
                                <td class="text-end fw-bold text-success">
                                    -<?= e($formatPercentage((float) $coupon->percentage)) ?> %
                                </td>
                                <td class="small">
                                    <?php if ($coupon->min_order_amount !== null): ?>
                                        <div>Min. <?= e(money($coupon->min_order_amount)) ?></div>
                                    <?php endif; ?>
                                    <?php if ($coupon->max_discount_amount !== null): ?>
                                        <div>Plafond <?= e(money($coupon->max_discount_amount)) ?></div>
                                    <?php endif; ?>
                                    <?php if (is_array($coupon->product_ids) && $coupon->product_ids !== []): ?>
                                        <div><?= count($coupon->product_ids) ?> produit(s) concerné(s)</div>
                                    <?php else: ?>
                                        <div class="text-muted-2">Tous les produits</div>
                                    <?php endif; ?>
                                    <?php if (!$coupon->isGlobal()): ?>
                                        <div class="text-muted-2">Client #<?= (int) $coupon->user_id ?> uniquement</div>
                                    <?php endif; ?>
                                    <div class="text-muted-2">
                                        Expire le <?= e($coupon->expires_at === null ? '—' : format_date($coupon->expires_at)) ?>
                                    </div>
                                </td>
                                <td class="text-center small">
                                    <?= (int) $coupon->used_count ?>
                                    <?php if ($coupon->usage_limit !== null): ?>
                                        / <?= (int) $coupon->usage_limit ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= e($tone) ?>"><?= e($label) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="<?= e(url('/admin/codes-promo?modifier=' . (int) $coupon->id)) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="post"
                                              action="<?= e(url('/admin/codes-promo/' . (int) $coupon->id . '/supprimer')) ?>"
                                              onsubmit="return confirm('Supprimer ce code promo ?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
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

    <div class="col-xl-4">
        <div class="table-card">
            <h2 class="h6 fw-bold mb-3">
                <?= $editing !== null ? 'Modifier « ' . e($editing->code) . ' »' : 'Nouveau code promo' ?>
            </h2>

            <form method="post"
                  action="<?= e($editing !== null ? url('/admin/codes-promo/' . (int) $editing->id) : url('/admin/codes-promo')) ?>">
                <?= csrf_field() ?>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="code">Code</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" id="code" name="code"
                           placeholder="Généré automatiquement si vide"
                           value="<?= e($editing?->code ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="percentage">Remise en % *</label>
                    <input type="number" class="form-control form-control-sm" id="percentage" name="percentage"
                           step="0.01" min="0.01" max="100" required
                           value="<?= e($editing?->percentage ?? '10') ?>">
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="minOrderAmount">Montant minimum</label>
                        <input type="number" class="form-control form-control-sm" id="minOrderAmount"
                               name="minOrderAmount" step="0.01" min="0"
                               value="<?= e($editing?->min_order_amount ?? '') ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold" for="maxDiscountAmount">Plafond de remise</label>
                        <input type="number" class="form-control form-control-sm" id="maxDiscountAmount"
                               name="maxDiscountAmount" step="0.01" min="0"
                               value="<?= e($editing?->max_discount_amount ?? '') ?>">
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="usageLimit">Nombre d'utilisations max</label>
                    <input type="number" class="form-control form-control-sm" id="usageLimit" name="usageLimit"
                           min="1" placeholder="Illimité si vide"
                           value="<?= e($editing?->usage_limit ?? '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="expiresAt">Date d'expiration</label>
                    <input type="datetime-local" class="form-control form-control-sm" id="expiresAt"
                           name="expiresAt"
                           value="<?= e($editing?->expires_at !== null ? date('Y-m-d\TH:i', strtotime((string) $editing->expires_at)) : '') ?>">
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="userId">Client ciblé</label>
                    <select class="form-select form-select-sm" id="userId" name="userId">
                        <option value="">— Tous les clients (code global) —</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= (int) $customer->id ?>"
                                <?= selected($editing?->user_id ?? '', $customer->id) ?>>
                                <?= e(trim((string) $customer->username) . ' — ' . (string) $customer->email) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-2">
                    <label class="form-label small fw-semibold" for="productIds">
                        Produits concernés (identifiants séparés par des virgules)
                    </label>
                    <input type="text" class="form-control form-control-sm" id="productIds" name="productIds"
                           placeholder="Vide = tous les produits"
                           value="<?= e($restrictedIds === [] ? '' : implode(', ', $restrictedIds)) ?>">
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="isActive" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" id="isActive" name="isActive"
                           value="1" <?= checked($editing === null ? true : (bool) $editing->is_active) ?>>
                    <label class="form-check-label small" for="isActive">Code actif</label>
                </div>

                <button class="btn btn-primary w-100" type="submit">
                    <?= $editing !== null ? 'Enregistrer' : 'Créer le code' ?>
                </button>
                <?php if ($editing !== null): ?>
                    <a class="btn btn-link w-100 mt-1" href="<?= e(url('/admin/codes-promo')) ?>">Annuler</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>