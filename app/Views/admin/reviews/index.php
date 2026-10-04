<?php
/**
 * Back-office : modération des avis clients.
 *
 * @var \App\Core\Paginator $paginator
 * @var array<string, int> $counts
 */
use App\Core\View;
use App\Models\Review;

$reviews = $paginator->items;

$filters = [
    ''                        => ['Tous', null],
    Review::STATUS_PENDING    => ['En attente', $counts['pending']],
    Review::STATUS_APPROVED   => ['Publiés', $counts['approved']],
    Review::STATUS_REJECTED   => ['Rejetés', $counts['rejected']],
];

$badgeClass = [
    Review::STATUS_APPROVED => 'success',
    Review::STATUS_REJECTED => 'danger',
    Review::STATUS_PENDING  => 'warning',
];
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <?php foreach ($filters as $value => [$label, $count]): ?>
        <?php $isActive = ($status ?? '') === $value; ?>
        <a class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline-secondary' ?>"
           href="<?= e(url('/admin/avis' . ($value !== '' ? '?statut=' . $value : ''))) ?>">
            <?= e($label) ?>
            <?php if ($count !== null): ?>
                <span class="badge bg-<?= $isActive ? 'light' : 'secondary' ?> text-<?= $isActive ? 'primary' : 'light' ?>">
                    <?= (int) $count ?>
                </span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="table-card">
    <?php if ($reviews === []): ?>
        <div class="text-center py-5">
            <i class="bi bi-chat-square-text fs-1 text-muted-2"></i>
            <p class="mt-3 mb-0 text-muted-2">
                <?= ($status ?? '') === '' ? 'Aucun avis pour le moment.' : 'Aucun avis dans cet état.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Client</th>
                        <th style="width:150px">Note</th>
                        <th>Avis</th>
                        <th class="text-center">État</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reviews as $review): ?>
                    <?php $reviewStatus = strtoupper((string) $review->status); ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e(img($review->product_thumbnail)) ?>" alt="" class="thumb-sm">
                                <div>
                                    <div class="fw-semibold">
                                        <?php if ($review->product_id !== null): ?>
                                            <a href="<?= e(url('/produit/' . (int) $review->product_id)) ?>" target="_blank">
                                                <?= e(excerpt($review->product_name ?? '—', 32)) ?>
                                            </a>
                                        <?php else: ?>
                                            <?= e(excerpt($review->product_name ?? '—', 32)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted-2"><?= e(format_date($review->created_at)) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="small">
                            <div class="fw-semibold"><?= e($review->authorName()) ?></div>
                            <?php if ($review->is_verified_purchase): ?>
                                <span class="badge bg-light text-dark border" style="font-size:.7rem">
                                    <i class="bi bi-patch-check me-1"></i>Achat vérifié
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= stars((float) $review->rating) ?></td>
                        <td style="max-width:340px">
                            <div class="small"><?= nl2br(e(excerpt($review->comment, 180))) ?></div>
                            <?php if (is_array($review->images) && $review->images !== []): ?>
                                <div class="d-flex gap-1 mt-1">
                                    <?php foreach (array_slice($review->images, 0, 4) as $photo): ?>
                                        <img src="<?= e(img($photo)) ?>" alt="" width="34" height="34"
                                             class="rounded" style="object-fit:cover">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= e($badgeClass[$reviewStatus] ?? 'secondary') ?>">
                                <?= e($review->statusLabel()) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <?php if ($reviewStatus !== Review::STATUS_APPROVED): ?>
                                    <form method="post"
                                          action="<?= e(url('/admin/avis/' . (int) $review->id . '/approuver')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-success" type="submit" title="Publier">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($reviewStatus !== Review::STATUS_REJECTED): ?>
                                    <form method="post"
                                          action="<?= e(url('/admin/avis/' . (int) $review->id . '/rejeter')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-warning" type="submit" title="Rejeter">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="post"
                                      action="<?= e(url('/admin/avis/' . (int) $review->id . '/supprimer')) ?>"
                                      onsubmit="return confirm('Supprimer définitivement cet avis ?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Supprimer">
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

        <?php View::partial('partials.pagination', ['paginator' => $paginator]); ?>
    <?php endif; ?>
</div>