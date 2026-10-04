<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Envoi des réponses HTTP (JSON pour l'API, HTML pour la boutique).
 */
final class Response
{
    public static function json(mixed $data = null, int $status = 200): never
    {
        self::sendJson($data, $status);
        exit;
    }

    public static function created(mixed $data = null, string $message = ''): never
    {
        self::sendJson($data, 201, $message);
        exit;
    }

    public static function ok(mixed $data = null, string $message = ''): never
    {
        self::sendJson($data, 200, $message);
        exit;
    }

    public static function error(string $message, int $status = 400, array $errors = []): never
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }
        self::sendJson($payload, $status, $message);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    public static function sendJson(mixed $data = null, int $status = 200, ?string $message = null): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        }

        // Les endpoints REST renvoient la ressource telle quelle (le client mobile
        // attend par exemple { content, totalElements, number, size }).
        $payload = $data;
        if ($message !== null && is_array($payload)) {
            $payload['message'] = $message;
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public static function html(string $html, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/html; charset=utf-8');
        }
        echo $html;
        exit;
    }

    public static function text(string $text, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo $text;
        exit;
    }

    public static function redirect(string $path, int $status = 302): never
    {
        $url = preg_match('#^https?://#i', $path) === 1 ? $path : url($path);
        if (!headers_sent()) {
            header('Location: ' . $url, true, $status);
        }
        exit;
    }

    public static function back(string $fallback = '/'): never
    {
        self::redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
    }

    /**
     * Affiche une page d'erreur (JSON pour l'API, HTML pour la boutique).
     */
    public static function failure(string $message, int $status = 500, ?Throwable $exception = null): never
    {
        $request = Request::instance();
        $debug = (bool) config('app.debug', false);

        if ($request->wantsJson()) {
            $payload = ['success' => false, 'message' => $message];
            if ($debug && $exception !== null) {
                $payload['exception'] = $exception->getMessage();
                $payload['file'] = $exception->getFile() . ':' . $exception->getLine();
            }
            self::sendJson($payload, $status);
            exit;
        }

        if ($debug && $exception !== null) {
            $detail = sprintf(
                '<h2>%s</h2><p><strong>%s</strong></p><pre>%s</pre><p>%s:%d</p>',
                htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($exception->getTraceAsString(), ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($exception->getFile(), ENT_QUOTES, 'UTF-8'),
                $exception->getLine()
            );
            self::html('<!doctype html><html lang="fr"><meta charset="utf-8"><title>Erreur</title>'
                . '<body style="font-family:system-ui;padding:2rem">' . $detail . '</body></html>', $status);
        }

        $view = APP_PATH . '/Views/errors/error.php';
        $html = is_file($view)
            ? (static function (string $path, int $code): string {
                ob_start();
                require $path;
                return (string) ob_get_clean();
            })($view, $status)
            : '<!doctype html><html lang="fr"><meta charset="utf-8"><title>Erreur ' . $status . '</title>'
              . '<body style="font-family:system-ui;padding:2rem"><h1>Erreur ' . $status . '</h1><p>'
              . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';

        self::html($html, $status);
    }

    /**
     * Erreur HTTP "objet" : permet de lever une 404/403 propre depuis un contrôleur.
     */
    public static function abort(string $message, int $status = 404): never
    {
        throw new HttpException($message, $status);
    }

    public static function abortUnless(bool $condition, string $message = 'Accès refusé', int $status = 403): void
    {
        if (!$condition) {
            throw new HttpException($message, $status);
        }
    }
}
