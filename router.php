<?php
/**
 * Router for PHP built-in server.
 * Handles URL rewriting for both web and API routes.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API routes: /api/* -> api/index.php
if (str_starts_with($uri, '/api/')) {
    require __DIR__ . '/api/index.php';
    return;
}

// Web routes: serve static files or route through index.php
$publicPath = __DIR__ . '/public' . $uri;
if (is_file($publicPath)) {
    return false; // serve static file
}

// All other routes go through the main index.php
require __DIR__ . '/index.php';