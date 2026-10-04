<?php
/**
 * Pagination HTML (la boutique utilise des pages en base 1).
 * @var \App\Core\Paginator $paginator
 */
$paginator = $paginator ?? null;
if ($paginator === null || !$paginator->hasPages()) {
    return;
}

$current = $paginator->currentPage();
$pages = $paginator->window(2);
?>
<nav aria-label="Pagination" class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($paginator->url($current - 1)) ?>" aria-label="Précédent">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>

        <?php if ($pages[0] > 1): ?>
            <li class="page-item"><a class="page-link" href="<?= e($paginator->url(1)) ?>">1</a></li>
            <?php if ($pages[0] > 2): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
        <?php endif; ?>

        <?php foreach ($pages as $page): ?>
            <li class="page-item <?= $page === $current ? 'active' : '' ?>">
                <a class="page-link" href="<?= e($paginator->url($page)) ?>"><?= $page ?></a>
            </li>
        <?php endforeach; ?>

        <?php if (end($pages) < $paginator->totalPages()): ?>
            <?php if (end($pages) < $paginator->totalPages() - 1): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item"><a class="page-link" href="<?= e($paginator->url($paginator->totalPages())) ?>"><?= $paginator->totalPages() ?></a></li>
        <?php endif; ?>

        <li class="page-item <?= $current >= $paginator->totalPages() ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= e($paginator->url($current + 1)) ?>" aria-label="Suivant">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
    </ul>
    <p class="text-center small text-muted-2">
        Page <?= $current ?> sur <?= $paginator->totalPages() ?> — <?= (int) $paginator->total ?> produit(s)
    </p>
</nav>