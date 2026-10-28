<?php

namespace App\Core;

class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/');
    }

    /**
     * Rend un template PHP avec les données fournies.
     * $view = 'home/index'  →  app/Views/home/index.php
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $viewFile = self::$viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("Vue introuvable : {$viewFile}");
        }

        // Extrait $data comme variables locales dans la vue
        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::$viewPath . '/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout introuvable : {$layoutFile}");
        }

        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }

    /** Affiche directement (echo). */
    public static function make(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        echo self::render($view, $data, $layout);
    }

    /** Rend un partial sans layout. */
    public static function partial(string $view, array $data = []): string
    {
        return self::render($view, $data, null);
    }
}

/**
 * Échappe une valeur pour l'affichage HTML (htmlspecialchars raccourci).
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Résout une URL d'asset (image, fichier uploadé).
 * - URL absolue (https://...) → retournée telle quelle
 * - Chemin relatif (uploads/...) → préfixé par APP_URL
 * - Vide → chaîne vide
 */
function asset_url(string|null $path): string
{
    if (empty($path)) return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
        return $path;
    }
    return rtrim(env('APP_URL', ''), '/') . '/' . ltrim($path, '/');
}
