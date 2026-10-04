<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Représentation de la requête HTTP courante (supporte JSON, formulaire, query string).
 */
final class Request
{
    private static ?self $instance = null;

    /** Préfixe d'installation retiré des chemins (ex. « /ecom_web_platform »). */
    private static string $basePath = '';

    /** Vrai lorsque la requête est traitée par api/index.php. */
    private static bool $isApi = false;

    private array $query;
    private array $body;
    private array $files;
    private array $headers;
    private array $routeParams = [];

    private function __construct(
        private readonly string $method,
        private readonly string $path,
        array $query,
        array $body,
        array $files,
        array $headers
    ) {
        $this->query = $query;
        $this->body = $body;
        $this->files = $files;
        $this->headers = $headers;
    }

    public static function capture(): self
    {
        if (self::$instance === null) {
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = parse_url($uri, PHP_URL_PATH) ?: '/';

            self::$basePath = self::detectBasePath();
            $path = self::stripBasePath($path);
            $path = '/' . trim($path, '/');

            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$name] = (string) $value;
                }
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $headers['Content-Type'] = (string) $_SERVER['CONTENT_TYPE'];
            }
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = (string) $_SERVER['HTTP_AUTHORIZATION'];
            }
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            }

            self::$instance = new self(
                $method,
                $path,
                $_GET,
                self::parseBody($headers),
                $_FILES,
                $headers
            );
        }

        return self::$instance;
    }

    public static function instance(): self
    {
        return self::capture();
    }

    /**
     * Répertoire d'installation, déduit de SCRIPT_NAME.
     *
     * - boutique : /ecom_web_platform/index.php  → « /ecom_web_platform »
     * - API      : /ecom_web_platform/api/index.php → « /ecom_web_platform/api »
     *
     * Le préfixe est retiré du chemin analysé afin que les routes déclarées
     * dans app/routes/*.php restent indépendantes du dossier d'installation.
     */
    private static function detectBasePath(): string
    {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = $script !== '' ? dirname($script) : '';

        if ($base === '/' || $base === '.' || $base === '') {
            $configured = parse_url((string) config('app.url', ''), PHP_URL_PATH);
            $base = is_string($configured) && $configured !== '' ? $configured : '/';
        }

        $base = '/' . trim($base, '/');

        return $base === '/' ? '' : $base;
    }

    private static function stripBasePath(string $path): string
    {
        if (self::$basePath === '') {
            return $path;
        }
        if ($path === self::$basePath || str_starts_with($path, self::$basePath . '/')) {
            return substr($path, strlen(self::$basePath));
        }

        return $path;
    }

    public static function basePath(): string
    {
        return self::$basePath;
    }

    /** Appelé par api/index.php pour indiquer que la réponse est du JSON. */
    public static function markAsApi(): void
    {
        self::$isApi = true;
    }

    public static function isApi(): bool
    {
        return self::$isApi;
    }

    private static function parseBody(array $headers): array
    {
        $contentType = $headers['Content-Type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (str_contains($contentType, 'application/x-www-form-urlencoded') || $_POST !== []) {
            return $_POST;
        }

        // Requête PUT/DELETE form-data : on relit php://input.
        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '' && !str_contains($contentType, 'multipart/form-data')) {
            parse_str($raw, $parsed);
            if ($parsed !== []) {
                return $parsed;
            }
        }

        return $_POST;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** Paramètre du body ou de la query string. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key);

        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'on', 'yes', 'oui'], true);
    }

    /** @return array<string, mixed> */
    public function array(string $key): array
    {
        $value = $this->input($key, []);

        return is_array($value) ? $value : [];
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function body(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        $data = $this->all();
        $filtered = [];
        foreach ($keys as $key) {
            $filtered[$key] = $data[$key] ?? null;
        }

        return $filtered;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[$name] ?? $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if ($header === null) {
            return null;
        }
        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    public function token(string $key, ?string $default = null): ?string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function intParam(string $key, int $default = 0): int
    {
        $value = $this->routeParams[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    public function setParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function hasParam(string $key): bool
    {
        return isset($this->routeParams[$key]);
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function wantsJson(): bool
    {
        if (self::$isApi) {
            return true;
        }

        $accept = $this->header('Accept', '') ?? '';
        $requestedWith = $this->header('X-Requested-With', '') ?? '';

        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return strcasecmp($requestedWith, 'XMLHttpRequest') === 0;
    }

    public function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function fullUrl(): string
    {
        return (string) ($_SERVER['REQUEST_URI'] ?? $this->path);
    }

    public function url(): string
    {
        $scheme = ($_SERVER['HTTPS'] ?? '') === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $this->fullUrl();
    }

    public function referer(?string $default = '/'): string
    {
        $referer = $this->header('Referer');

        return $referer !== null && $referer !== '' ? $referer : $default;
    }
}
