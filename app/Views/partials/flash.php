<?php
/**
 * Messages flash (succès / erreur).
 * @var array<int, array{type: string, message: string}> $flashMessages
 */
$flashMessages = $flashMessages ?? \App\Core\Session::pullFlash();

if ($flashMessages === []) {
    return;
}

$styles = [
    'success' => ['success', 'check-circle'],
    'danger'  => ['danger', 'exclamation-triangle'],
    'warning' => ['warning', 'exclamation-circle'],
    'info'    => ['info', 'info-circle'],
];

foreach ($flashMessages as $flash) {
    [$class, $icon] = $styles[$flash['type']] ?? ['info', 'info-circle'];
    ?>
    <div class="alert alert-<?= e($class) ?> alert-dismissible fade show d-flex align-items-center gap-2" data-auto-dismiss>
        <i class="bi bi-<?= e($icon) ?>"></i>
        <div><?= e($flash['message']) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
    <?php
}