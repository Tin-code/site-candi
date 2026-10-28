<?php

namespace App\Controllers\App;

use App\Core\Controller;

abstract class AppController extends Controller
{
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        parent::view($view, $data, $layout);
    }

    /**
     * Gère l'upload d'un fichier image depuis $_FILES.
     * Retourne le chemin relatif (ex. "uploads/covers/abc.jpg") ou null.
     * Si $removeFlag est true, supprime l'image existante et retourne ''.
     */
    protected function handleImageUpload(
        string $fileKey,
        string $subDir,
        ?string $existing = null,
        bool $removeFlag = false
    ): ?string {
        $uploadRoot = dirname(__DIR__, 3) . '/public/uploads/' . trim($subDir, '/');
        if (!is_dir($uploadRoot)) {
            mkdir($uploadRoot, 0755, true);
        }

        // Suppression demandée sans nouvel upload
        if ($removeFlag && empty($_FILES[$fileKey]['size'])) {
            if ($existing) {
                $abs = dirname(__DIR__, 3) . '/public/' . ltrim($existing, '/');
                if (is_file($abs)) @unlink($abs);
            }
            return '';
        }

        if (empty($_FILES[$fileKey]['tmp_name']) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null; // pas de fichier → garder l'existant
        }

        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime    = mime_content_type($_FILES[$fileKey]['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }

        $ext      = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
        $filename = uniqid('img_', true) . '.' . $ext;
        $dest     = $uploadRoot . '/' . $filename;

        if (!move_uploaded_file($_FILES[$fileKey]['tmp_name'], $dest)) {
            return null;
        }

        // Supprimer l'ancienne image si elle est dans notre uploads/
        if ($existing && str_starts_with($existing, 'uploads/')) {
            $abs = dirname(__DIR__, 3) . '/public/' . $existing;
            if (is_file($abs)) @unlink($abs);
        }

        return 'uploads/' . trim($subDir, '/') . '/' . $filename;
    }
}
