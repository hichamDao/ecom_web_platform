<?php
/**
 * Point d'entrÃ©e de la boutique web et du back-office :
 *   http://serveur/ecom_web_platform/            â†’ vitrine
 *   http://serveur/ecom_web_platform/admin       â†’ back-office
 * L'API REST est servie sÃ©parÃ©ment par api/index.php.
 */
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

use App\Core\Csrf;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Notification;
use App\Models\PromoBanner;
use App\Models\Setting;

$config = $GLOBALS['config'];

// 1. Base de donnÃ©es
try {
    Database::boot($config['db']);
} catch (Throwable $e) {
    http_response_code(503);
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Indisponible</title>'
        . '<body style="font-family:system-ui;padding:3rem;max-width:720px;margin:auto">'
        . '<h1>Base de donnÃ©es inaccessible</h1>'
        . '<p>VÃ©rifiez que MySQL est dÃ©marrÃ© et que la configuration de '
        . '<code>config/config.php</code> est correcte.</p>'
        . (config('app.debug', false)
            ? '<pre style="background:#f6f6f6;padding:1rem;border-radius:8px">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>'
            : '')
        . '</body></html>';
    exit;
}

Session::start();
$request = Request::capture();

// 2. Protection CSRF sur toutes les requÃªtes POST
try {
    Csrf::verifyRequest($request);
} catch (HttpException $e) {
    Response::failure($e->getMessage(), $e->status());
    exit;
}

// 3. DonnÃ©es partagÃ©es avec toutes les vues
$currentUser = \App\Core\Auth::user();
$cartCount = 0;
if ($currentUser !== null) {
    $cartCount = Cart::forUser((int) $currentUser->id)->totalItems();
}

View::shareMany([
    'appName'       => (string) config('app.name', 'EcomWeb'),
    'appTagline'    => (string) config('app.tagline', ''),
    'categories'    => Category::tree(),
    'banners'       => PromoBanner::active(),
    'currentUser'   => $currentUser,
    'cartCount'     => $cartCount,
    'unreadCount'   => $currentUser === null ? 0 : Notification::unreadCount((int) $currentUser->id),
    'storeEmail'    => Setting::read('store_email', 'contact@ecomweb.ma'),
    'storePhone'    => Setting::read('store_phone', ''),
    'storeAddress'  => Setting::read('store_address', ''),
    'freeShipping'  => free_shipping_from(),
    'flashMessages' => Session::pullFlash(),
]);

// 4. Routage
$router = new Router();

try {
    foreach (require APP_PATH . '/routes/web.php' as $definition => $handler) {
        // « GET  /boutique » : le séparateur peut être espacé pour aligner les définitions.
        if (preg_match('/^(\w+)\s+(.+)$/', $definition, $matches) !== 1) {
            continue;
        }
        $router->add($matches[1], '/' . ltrim(trim($matches[2]), '/'), $handler);
    }

    $router->dispatch($request);
} catch (HttpException $e) {
    Response::failure($e->getMessage(), $e->status());
} catch (Throwable $e) {
    Response::failure(
        config('app.debug', false) ? $e->getMessage() : 'Une erreur interne est survenue.',
        500,
        $e
    );
}