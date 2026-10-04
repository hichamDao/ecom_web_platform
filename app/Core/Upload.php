<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Dépôt d'images (fiche produit, bannières) dans public/uploads.
 */
final class Upload
{
    public const MAX_SIZE = 4 * 1024 * 1024; // 4 Mo
    public const ALLOWED = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Enregistre un fichier téléversé et renvoie son chemin relatif ("uploads/products/xxx.jpg").
     */
    public static function image(?array $file, string $folder = 'products'): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? 0) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Le téléchargement du fichier a échoué.');
        }
        if (($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException('Fichier trop volumineux (4 Mo maximum).');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED, true)) {
            throw new RuntimeException('Format d\'image non supporté (jpg, jpeg, png, gif, webp).');
        }

        $directory = PUBLIC_PATH . '/uploads/' . trim($folder, '/');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le dossier d\'upload.');
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $directory . '/' . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Impossible d\'enregistrer le fichier.');
        }

        return 'uploads/' . trim($folder, '/') . '/' . $filename;
    }

    /** Supprime un fichier uploadé (chemin relatif). */
    public static function delete(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }
        if (str_starts_with($relativePath, 'http')) {
            return;
        }

        $path = PUBLIC_PATH . '/' . ltrim($relativePath, '/');
        if (is_file($path)) {
            unlink($path);
        }
    }
}