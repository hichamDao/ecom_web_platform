<?php
/**
 * Page d'erreur (404, 403, 500...).
 * @var string $message
 * @var int    $code
 */
$code = $code ?? 500;
$titles = [
    403 => 'Accès refusé',
    404 => 'Page introuvable',
    419 => 'Session expirée',
    500 => 'Erreur serveur',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur <?= (int) $code ?> · <?= e(config('app.name', 'EcomWeb')) ?></title>
    <link rel="icon" href="<?= e(asset('assets/img/placeholder.svg')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="<?= e(asset('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="container py-5 text-center">
    <div class="display-1 fw-bold text-primary"><?= (int) $code ?></div>
    <h1 class="h3 mb-3"><?= e($titles[$code] ?? 'Une erreur est survenue') ?></h1>
    <p class="text-muted-2 mb-4"><?= e($message ?? '') ?></p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>"><i class="bi bi-house me-1"></i>Retour à l'accueil</a>
</div>
</body>
</html>