<?php
declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;

/**
 * Routeur minimal : routes statiques (/panier) prioritaires sur les routes
 * dynamiques (/produit/{id}).
 *
 * Un handler est soit une closure, soit la chaîne "Namespace\Classe@methode".
 */
final class Router
{
    /** @var array<string, array<string, callable|array{0: string, 1: string}>> */
    private array $static = [];

    /** @var array<string, array<int, array{segments: array<int, string>, handler: mixed}>> */
    private array $dynamic = [];

    private string $prefix = '';

    public function prefix(string $prefix): static
    {
        $this->prefix = rtrim($prefix, '/');

        return $this;
    }

    public function get(string $path, mixed $handler): static
    {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, mixed $handler): static
    {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, mixed $handler): static
    {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, mixed $handler): static
    {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, mixed $handler): static
    {
        return $this->add('DELETE', $path, $handler);
    }

    /** Enregistre la même action pour plusieurs verbes HTTP. */
    public function map(array $methods, string $path, mixed $handler): static
    {
        foreach ($methods as $method) {
            $this->add($method, $path, $handler);
        }

        return $this;
    }

    public function add(string $method, string $path, mixed $handler): static
    {
        $method = strtoupper($method);
        $path = $this->normalize($this->prefix . $path);

        if (!str_contains($path, '{')) {
            $this->static[$method][$path] = $handler;

            return $this;
        }

        $this->dynamic[$method][] = [
            'segments' => explode('/', trim($path, '/')),
            'handler'  => $handler,
        ];

        return $this;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function dispatch(Request $request): mixed
    {
        $method = $request->method();
        $path = $request->path();

        // 1. Correspondance exacte
        if (isset($this->static[$method][$path])) {
            return $this->call($this->static[$method][$path], $request, []);
        }

        // 2. Correspondance dynamique
        $requestSegments = explode('/', trim($path, '/'));
        foreach ($this->dynamic[$method] ?? [] as $route) {
            $params = $this->matchSegments($route['segments'], $requestSegments);
            if ($params !== null) {
                return $this->call($route['handler'], $request, $params);
            }
        }

        // 3. Chemin connu mais mauvais verbe HTTP
        foreach (array_keys($this->static) as $otherMethod) {
            if (isset($this->static[$otherMethod][$path])) {
                throw new HttpException('Méthode ' . $method . ' non autorisée.', 405);
            }
        }

        throw HttpException::notFound('Page introuvable : ' . $path);
    }

    /**
     * @param  array<int, string> $segments
     * @param  array<int, string> $requestSegments
     * @return array<string, string|int>|null
     */
    private function matchSegments(array $segments, array $requestSegments): ?array
    {
        if (count($segments) !== count($requestSegments)) {
            return null;
        }

        $params = [];
        foreach ($segments as $index => $segment) {
            if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                $name = substr($segment, 1, -1);
                $value = urldecode($requestSegments[$index]);

                // Les contrôleurs déclarent souvent « int $id » et le projet utilise
                // strict_types : on convertit donc les segments purement numériques.
                if (ctype_digit($value)) {
                    $params[$name] = (int) $value;
                } else {
                    $params[$name] = $value;
                }
                continue;
            }
            if ($segment !== $requestSegments[$index]) {
                return null;
            }
        }

        return $params;
    }

    private function call(mixed $handler, Request $request, array $params): mixed
    {
        $request->setParams($params);

        if ($handler instanceof Closure) {
            return $handler($request);
        }

        if (is_string($handler)) {
            if (!str_contains($handler, '@')) {
                throw new InvalidArgumentException('Handler invalide : ' . $handler);
            }
            [$class, $method] = explode('@', $handler, 2);
            $class = str_starts_with($class, '\\') ? ltrim($class, '\\') : $class;

            if (!class_exists($class)) {
                throw new InvalidArgumentException('Contrôleur introuvable : ' . $class);
            }

            $controller = new $class();
            if (!method_exists($controller, $method)) {
                throw new InvalidArgumentException('Méthode introuvable : ' . $class . '::' . $method);
            }

            return $controller->{$method}(...array_values($params));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = is_object($class) ? $class : new $class();

            return $controller->{$method}(...array_values($params));
        }

        throw new InvalidArgumentException('Handler de route non supporté.');
    }

    /** @return array<int, string> */
    public function routes(): array
    {
        $paths = [];
        foreach ($this->static as $method => $routes) {
            foreach (array_keys($routes) as $path) {
                $paths[] = strtoupper($method) . ' ' . $path;
            }
        }
        foreach ($this->dynamic as $method => $routes) {
            foreach ($routes as $route) {
                $paths[] = strtoupper($method) . ' /' . implode('/', $route['segments']);
            }
        }
        sort($paths);

        return $paths;
    }
}
