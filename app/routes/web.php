<?php
declare(strict_types=1);

/**
 * Routes de la boutique web et du back-office.
 *
 * Format : "VERBE /chemin" => [Contrôleur::class, 'méthode']
 * Les contrôles d'accès sont effectués dans les contrôleurs (Auth::check(),
 * Auth::isAdmin()), la protection CSRF est appliquée globalement dans index.php.
 */

use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\CouponController as AdminCouponController;
use App\Controllers\Admin\CustomerController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OrderController as AdminOrderController;
use App\Controllers\Admin\ProductController as AdminProductController;
use App\Controllers\Admin\PromoBannerController as AdminBannerController;
use App\Controllers\Admin\ReviewController as AdminReviewController;
use App\Controllers\Web\AccountController;
use App\Controllers\Web\CartController;
use App\Controllers\Web\CheckoutController;
use App\Controllers\Web\HomeController;

return [
    // ---- Vitrine -----------------------------------------------------
    'GET  /'                             => [HomeController::class, 'index'],
    'GET  /boutique'                     => [HomeController::class, 'shop'],
    'GET  /recherche'                    => [HomeController::class, 'search'],
    'GET  /categorie/{id}'               => [HomeController::class, 'category'],
    'GET  /produit/{id}'                 => [HomeController::class, 'product'],
    'POST /produit/{id}/avis'            => [HomeController::class, 'storeReview'],
    'POST /favoris/{id}'                 => [HomeController::class, 'toggleWishlist'],

    // ---- Panier ------------------------------------------------------
    'GET  /panier'                       => [CartController::class, 'index'],
    'POST /panier/ajouter'               => [CartController::class, 'store'],
    'POST /panier/modifier/{itemId}'     => [CartController::class, 'update'],
    'POST /panier/supprimer/{itemId}'    => [CartController::class, 'destroy'],
    'POST /panier/vider'                 => [CartController::class, 'clear'],
    'POST /panier/code'                  => [CartController::class, 'applyCoupon'],
    'POST /panier/code/retirer'          => [CartController::class, 'removeCoupon'],

    // ---- Commande ----------------------------------------------------
    'GET  /commande'                     => [CheckoutController::class, 'index'],
    'POST /commande'                     => [CheckoutController::class, 'store'],
    'GET  /commande/succes/{id}'         => [CheckoutController::class, 'confirmation'],

    // ---- Authentification ---------------------------------------------
    'GET  /connexion'                    => [AccountController::class, 'loginForm'],
    'POST /connexion'                    => [AccountController::class, 'login'],
    'GET  /inscription'                  => [AccountController::class, 'registerForm'],
    'POST /inscription'                  => [AccountController::class, 'register'],
    'POST /deconnexion'                  => [AccountController::class, 'logout'],

    // ---- Espace client ------------------------------------------------
    'GET  /compte'                       => [AccountController::class, 'profile'],
    'POST /compte'                       => [AccountController::class, 'updateProfile'],
    'POST /compte/mot-de-passe'          => [AccountController::class, 'changePassword'],
    'GET  /compte/commandes'             => [AccountController::class, 'orders'],
    'GET  /compte/commandes/{id}'        => [AccountController::class, 'orderDetail'],
    'POST /compte/commandes/{id}/annuler'=> [AccountController::class, 'cancelOrder'],
    'GET  /compte/favoris'               => [AccountController::class, 'wishlist'],
    'GET  /compte/adresses'              => [AccountController::class, 'addresses'],
    'POST /compte/adresses'              => [AccountController::class, 'storeAddress'],
    'POST /compte/adresses/{id}'         => [AccountController::class, 'updateAddress'],
    'POST /compte/adresses/{id}/defaut'  => [AccountController::class, 'defaultAddress'],
    'POST /compte/adresses/{id}/supprimer'=> [AccountController::class, 'deleteAddress'],

    // ---- Back-office : tableau de bord --------------------------------
    'GET  /admin'                        => [DashboardController::class, 'index'],
    'GET  /admin/statistiques'           => [DashboardController::class, 'sales'],

    // ---- Back-office : produits ---------------------------------------
    'GET  /admin/produits'               => [AdminProductController::class, 'index'],
    'GET  /admin/produits/nouveau'       => [AdminProductController::class, 'create'],
    'POST /admin/produits'               => [AdminProductController::class, 'store'],
    'GET  /admin/produits/{id}/modifier' => [AdminProductController::class, 'edit'],
    'POST /admin/produits/{id}'          => [AdminProductController::class, 'update'],
    'POST /admin/produits/{id}/supprimer'=> [AdminProductController::class, 'destroy'],

    // ---- Back-office : catégories -------------------------------------
    'GET  /admin/categories'             => [AdminCategoryController::class, 'index'],
    'POST /admin/categories'             => [AdminCategoryController::class, 'store'],
    'POST /admin/categories/{id}'        => [AdminCategoryController::class, 'update'],
    'POST /admin/categories/{id}/supprimer' => [AdminCategoryController::class, 'destroy'],

    // ---- Back-office : commandes --------------------------------------
    'GET  /admin/commandes'              => [AdminOrderController::class, 'index'],
    'GET  /admin/commandes/{id}'         => [AdminOrderController::class, 'show'],
    'POST /admin/commandes/{id}/statut'  => [AdminOrderController::class, 'updateStatus'],
    'POST /admin/commandes/{id}/paiement'=> [AdminOrderController::class, 'updatePayment'],

    // ---- Back-office : clients ----------------------------------------
    'GET  /admin/clients'                => [CustomerController::class, 'index'],
    'GET  /admin/clients/{id}'           => [CustomerController::class, 'show'],

    // ---- Back-office : avis -------------------------------------------
    'GET  /admin/avis'                   => [AdminReviewController::class, 'index'],
    'POST /admin/avis/{id}/approuver'    => [AdminReviewController::class, 'approve'],
    'POST /admin/avis/{id}/rejeter'      => [AdminReviewController::class, 'reject'],
    'POST /admin/avis/{id}/supprimer'    => [AdminReviewController::class, 'destroy'],

    // ---- Back-office : codes promo -------------------------------------
    'GET  /admin/codes-promo'            => [AdminCouponController::class, 'index'],
    'POST /admin/codes-promo'            => [AdminCouponController::class, 'store'],
    'POST /admin/codes-promo/{id}'       => [AdminCouponController::class, 'update'],
    'POST /admin/codes-promo/{id}/supprimer' => [AdminCouponController::class, 'destroy'],

    // ---- Back-office : bannières ----------------------------------------
    'GET  /admin/bannieres'              => [AdminBannerController::class, 'index'],
    'POST /admin/bannieres'              => [AdminBannerController::class, 'store'],
    'POST /admin/bannieres/{id}'         => [AdminBannerController::class, 'update'],
    'POST /admin/bannieres/{id}/supprimer' => [AdminBannerController::class, 'destroy'],
];