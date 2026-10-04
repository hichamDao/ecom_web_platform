<?php
/**
 * Configuration générale de la plateforme.
 * Adaptez les valeurs de connexion MySQL à votre environnement (XAMPP / WAMP).
 */
declare(strict_types=1);

return [
    'app' => [
        'name'     => 'EcomWeb',
        'tagline'  => 'La plateforme e-commerce complète',
        'debug'    => true,
        'timezone' => 'Africa/Casablanca',
        'locale'   => 'fr_MA',
        'currency' => 'MAD',
        // URL de base servie par Apache (sans slash final)
        'url'      => 'http://127.0.0.1:8080/ecommerce/ecom-projet-fend/ecom_web_platform',
    ],

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'ecom_platform',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'jwt' => [
        'secret' => 'changez-cette-cle-secrete-dans-un-environment-de-production',
        'ttl'    => 604800, // 7 jours en secondes
        'issuer' => 'ecom_web_platform',
    ],

    'shop' => [
        'shipping_fee'        => 40.0,
        'free_shipping_from'  => 1000.0,
        'default_page_size'   => 12,
        'low_stock_threshold' => 5,
    ],
];
