<?php
/**
 * Point d'entrée unique de l'API REST :  http://serveur/ecom_web_platform/api/...
 * Toutes les requêtes sont redirigées ici par .htaccess.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

$config = $GLOBALS['config'];

try {
    Database::boot($config['db']);
} catch (Throwable $e) {
    Response::sendJson([
        'success' => false,
        'message' => 'Service indisponible : la base de données est inaccessible.',
        'detail'  => config('app.debug', false) ? $e->getMessage() : null,
    ], 503);
    exit;
}

$request = Request::capture();
Request::markAsApi();

// Préflight CORS
if ($request->isMethod('OPTIONS')) {
    http_response_code(204);
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    exit;
}

$router = new Router();

try {
    foreach (require APP_PATH . '/routes/api.php' as $definition => $handler) {
        // « GET  products » : le séparateur peut être espacé pour aligner les définitions.
        if (preg_match('/^(\w+)\s+(.+)$/', $definition, $matches) !== 1) {
            continue;
        }
        $router->add($matches[1], '/' . ltrim(trim($matches[2]), '/'), $handler);
    }

    $router->dispatch($request);
} catch (HttpException $e) {
    Response::sendJson([
        'success' => false,
        'message' => $e->getMessage(),
        'errors'  => $e->errors(),
    ], $e->status());
} catch (Throwable $e) {
    Response::sendJson([
        'success'   => false,
        'message'   => config('app.debug', false) ? $e->getMessage() : 'Erreur interne du serveur.',
        'exception' => config('app.debug', false) ? $e::class : null,
        'file'      => config('app.debug', false) ? $e->getFile() . ':' . $e->getLine() : null,
    ], 500);
}