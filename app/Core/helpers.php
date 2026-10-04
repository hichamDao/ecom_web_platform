<?php
/**
 * Fonctions utilitaires globales (disponibles dans les vues et les contrÃ´leurs).
 */
declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;

if (!function_exists('config')) {
    /** AccÃ¨s Ã  la configuration par notation pointÃ©e : config('db.host') */
    function config(string $key, mixed $default = null): mixed
    {
        $config = $GLOBALS['config'] ?? [];
        $value = $config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

if (!function_exists('e')) {
    /** Ã‰chappe une valeur pour l'affichage HTML. */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('money')) {
    /** Formate un montant : 1 299.00 MAD */
    function money(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $formatted = number_format((float) $amount, 2, ',', ' ');

        return $withSymbol ? $formatted . ' ' . config('app.currency', 'MAD') : $formatted;
    }
}

if (!function_exists('url')) {
    /** URL absolue d'une route interne. */
    function url(string $path = '/'): string
    {
        if (preg_match('#^(https?:)?//#i', $path) === 1) {
            return $path;
        }

        return rtrim((string) config('app.url', ''), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('public/' . ltrim($path, '/'));
    }
}

if (!function_exists('img')) {
    /** URL d'une image stockÃ©e en base (URL absolue ou chemin relatif /public). */
    function img(?string $src, string $fallback = 'assets/img/placeholder.svg'): string
    {
        $src = trim((string) $src);
        if ($src === '') {
            return asset($fallback);
        }
        if (preg_match('#^(https?:)?//|^data:#i', $src) === 1) {
            return $src;
        }
        if (str_starts_with($src, 'public/')) {
            return url($src);
        }

        return url('public/' . ltrim($src, '/'));
    }
}

if (!function_exists('product_url')) {
    function product_url(Product|int|null $product): string
    {
        $id = $product instanceof Product ? (int) $product->id : (int) $product;

        return $id > 0 ? url('/produit/' . $id) : url('/boutique');
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return Request::instance();
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        Response::redirect($path);
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): never
    {
        Response::redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
    }
}

if (!function_exists('old')) {
    /** Ancienne valeur saisie dans un formulaire. */
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::old($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('auth')) {
    function auth(): ?User
    {
        return Auth::user();
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return Auth::check();
    }
}

if (!function_exists('auth_id')) {
    function auth_id(): ?int
    {
        return Auth::id();
    }
}

if (!function_exists('auth_is_admin')) {
    function auth_is_admin(): bool
    {
        return Auth::isAdmin();
    }
}

if (!function_exists('setting')) {
    /** ParamÃ¨tre stockÃ© en base (table settings). */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::read($key, $default);
    }
}

if (!function_exists('route_is')) {
    function route_is(string ...$paths): bool
    {
        $current = Request::instance()->path();

        foreach ($paths as $path) {
            if ($current === $path || rtrim($current, '/') === rtrim($path, '/')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('is_active')) {
    /** Classe CSS Bootstrap pour l'Ã©lÃ©ment de menu actif. */
    function is_active(string $path, string $class = 'active'): string
    {
        return route_is($path) ? $class : '';
    }
}

if (!function_exists('discount_price')) {
    function discount_price(float $price, float|int|string|null $percentage): float
    {
        $percentage = (float) $percentage;
        if ($percentage <= 0) {
            return $price;
        }

        return round($price - ($price * $percentage / 100), 2);
    }
}

if (!function_exists('final_price')) {
    /** Prix rÃ©ellement payÃ© (remise appliquÃ©e si old_price/discount sont dÃ©finis). */
    function final_price(Product|float $product, ?float $fallbackPercentage = null): float
    {
        if (!($product instanceof Product)) {
            return discount_price($product, $fallbackPercentage);
        }

        $percentage = (float) ($product->discount_percentage ?? 0);
        if ($percentage <= 0 && $product->old_price !== null && (float) $product->old_price > (float) $product->price) {
            return (float) $product->old_price;
        }

        return discount_price((float) $product->price, $percentage);
    }
}

if (!function_exists('order_statuses')) {
    /** @return array<string, string> */
    function order_statuses(): array
    {
        return [
            'PENDING'   => 'En attente',
            'CONFIRMED' => 'ConfirmÃ©',
            'PROCESSING'=> 'En traitement',
            'SHIPPED'   => 'ExpÃ©diÃ©',
            'DELIVERED' => 'LivrÃ©',
            'CANCELLED' => 'AnnulÃ©',
            'REFUNDED'  => 'RemboursÃ©',
        ];
    }
}

if (!function_exists('order_status_label')) {
    function order_status_label(?string $status): string
    {
        return order_statuses()[strtoupper((string) $status)] ?? 'Inconnu';
    }
}

if (!function_exists('order_status_badge')) {
    /** Classe Bootstrap correspondant au statut. */
    function order_status_badge(?string $status): string
    {
        return match (strtoupper((string) $status)) {
            'PENDING'    => 'warning',
            'CONFIRMED'  => 'info',
            'PROCESSING' => 'primary',
            'SHIPPED'    => 'info',
            'DELIVERED'  => 'success',
            'CANCELLED'  => 'secondary',
            'REFUNDED'   => 'dark',
            default      => 'light',
        };
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'd/m/Y H:i'): string
    {
        if ($date === null || $date === '') {
            return 'â€”';
        }
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return (string) $date;
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('iso_date')) {
    /** Convertit une date MySQL en chaÃ®ne ISO 8601 pour le JSON (Flutter). */
    function iso_date(mixed $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }
        $timestamp = is_numeric($date) ? (int) $date : strtotime((string) $date);
        if ($timestamp === false) {
            return null;
        }

        return date('c', $timestamp);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(?string $text, int $length = 140): string
    {
        $text = trim(strip_tags((string) $text));
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length), " \t\n\r\0\x0B.,;:") . 'â€¦';
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $text) ?? '');

        return trim($text, '-');
    }
}

if (!function_exists('stars')) {
    /** Affiche une notation sous forme d'Ã©toiles. */
    function stars(float $rating): string
    {
        $full = (int) floor($rating);
        $half = ($rating - $full) >= 0.5;
        $html = '<span class="stars">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $full) {
                $html .= '<i class="bi bi-star-fill"></i>';
            } elseif ($half && $i === $full + 1) {
                $html .= '<i class="bi bi-star-half"></i>';
            } else {
                $html .= '<i class="bi bi-star"></i>';
            }
        }

        return $html . '</span>';
    }
}

if (!function_exists('shipping_fee')) {
    function shipping_fee(): float
    {
        return (float) config('shop.shipping_fee', 40);
    }
}

if (!function_exists('free_shipping_from')) {
    function free_shipping_from(): float
    {
        return (float) config('shop.free_shipping_from', 1000);
    }
}

if (!function_exists('low_stock_threshold')) {
    function low_stock_threshold(): int
    {
        return (int) config('shop.low_stock_threshold', 5);
    }
}

if (!function_exists('nav_categories')) {
    /** CatÃ©gories racines avec leurs sous-catÃ©gories (menu principal). */
    function nav_categories(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Category::tree();
        }

        return $cache;
    }
}

if (!function_exists('selected')) {
    function selected(mixed $value, mixed $expected): string
    {
        return (string) $value === (string) $expected ? 'selected' : '';
    }
}

if (!function_exists('checked')) {
    function checked(mixed $value, bool $expected = true): string
    {
        return $value === $expected ? 'checked' : '';
    }
}

if (!function_exists('old_has')) {
    /** Ajoute la classe is-invalid si le champ a dÃ©jÃ  Ã©tÃ© soumis. */
    function old_has(string $field): string
    {
        return Session::has('_old') && old($field) !== '' ? ' is-invalid' : '';
    }
}

if (!function_exists('field_error')) {
    function field_error(string $field): string
    {
        return old_has($field);
    }
}

if (!function_exists('abort_unless')) {
    function abort_unless(bool $condition, string $message = 'Ressource introuvable', int $status = 404): void
    {
        if (!$condition) {
            throw new HttpException($message, $status);
        }
    }
}

if (!function_exists('flash_render')) {
    /** Affiche les messages flash (partials/flash.php). */
    function flash_render(): string
    {
        return View::capture('partials.flash');
    }
}
