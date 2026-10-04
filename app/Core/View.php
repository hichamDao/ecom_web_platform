<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Moteur de vues minimal : extraction des variables, inclusion du template,
 * habillage par un layout et sections (scripts, styles).
 */
final class View
{
    /** @var array<string, mixed> */
    private static array $shared = [];

    /** @var array<string, string> */
    private static array $sections = [];

    /** @var array<int, string> */
    private static array $stack = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /** @param array<string, mixed> $data */
    public static function shareMany(array $data): void
    {
        self::$shared = array_merge(self::$shared, $data);
    }

    /**
     * Rend une vue puis l'habille avec un layout.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::capture($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::capture($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Affiche la vue puis stoppe le script (utilisé dans les contrôleurs).
     *
     * @param array<string, mixed> $data
     */
    public static function display(string $view, array $data = [], ?string $layout = 'layouts/main'): never
    {
        Response::html(self::render($view, $data, $layout));
    }

    /** @param array<string, mixed> $data */
    public static function capture(string $view, array $data = []): string
    {
        $path = self::path($view);
        $variables = array_merge(self::$shared, $data, ['view' => $view]);

        extract($variables, EXTR_SKIP);
        ob_start();

        try {
            require $path;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    /** Inclut un fragment (partial) sans layout. */
    public static function partial(string $view, array $data = []): void
    {
        echo self::capture($view, $data);
    }

    public static function exists(string $view): bool
    {
        return is_file(self::path($view));
    }

    private static function path(string $view): string
    {
        $path = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (!is_file($path)) {
            throw new RuntimeException('Vue introuvable : ' . $view . ' (' . $path . ')');
        }

        return $path;
    }

    // -----------------------------------------------------------------
    // Sections
    // -----------------------------------------------------------------

    public static function start(string $name): void
    {
        self::$stack[] = $name;
        ob_start();
    }

    public static function stop(): void
    {
        $name = array_pop(self::$stack);
        if ($name === null) {
            return;
        }
        self::$sections[$name] = (self::$sections[$name] ?? '') . (string) ob_get_clean();
    }

    public static function section(string $name): string
    {
        return self::$sections[$name] ?? '';
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && self::$sections[$name] !== '';
    }
}
