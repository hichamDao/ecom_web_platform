<?php
/**
 * Amorçage de l'application : autoloader PSR-4, configuration, helpers.
 * Ce fichier est chargé par index.php (boutique) et api/index.php (API).
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('STORAGE_PATH', BASE_PATH . '/storage');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = APP_PATH . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

/** @var array<string, mixed> $config */
$config = require CONFIG_PATH . '/config.php';

$GLOBALS['config'] = $config;

date_default_timezone_set($config['app']['timezone'] ?? 'UTC');
mb_internal_encoding('UTF-8');

error_reporting(($config['app']['debug'] ?? false) ? E_ALL : E_ALL & ~E_DEPRECATED);
ini_set('display_errors', ($config['app']['debug'] ?? false) ? '1' : '0');

require APP_PATH . '/Core/helpers.php';
