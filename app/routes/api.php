<?php
declare(strict_types=1);

/**
 * Routes de l'API REST (consommée par l'application mobile ecom_app_fend).
 * Le préfixe /api est retiré par le point d'entrée api/index.php.
 *
 * Format de réponse : JSON. Les listes paginées utilisent la structure
 * { content, totalElements, totalPages, number, size } (compatible Spring Boot).
 */

use App\Controllers\Api\AddressController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\CartController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\CouponController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\OrderController;
use App\Controllers\Api\ProductController;
use App\Controllers\Api\PromoBannerController;
use App\Controllers\Api\ReviewController;
use App\Controllers\Api\UserController;
use App\Controllers\Api\WishlistController;
use App\Core\Router;

/** @var Router $router */
return [
    // ---- Authentification --------------------------------------------
    'POST auth/login'            => [AuthController::class, 'login'],
    'POST auth/register'         => [AuthController::class, 'register'],
    'POST auth/logout'           => [AuthController::class, 'logout'],
    'POST auth/change-password'  => [AuthController::class, 'changePassword'],
    'POST auth/forgot-password'  => [AuthController::class, 'forgotPassword'],

    // ---- Utilisateurs ------------------------------------------------
    'GET  users/me'              => [UserController::class, 'me'],
    'PUT  users/me'              => [UserController::class, 'updateMe'],
    'GET  users/all'             => [UserController::class, 'all'],
    'DELETE users/{id}'          => [UserController::class, 'destroy'],

    // ---- Produits ---------------------------------------------------
    'GET  products'              => [ProductController::class, 'index'],
    'POST products'              => [ProductController::class, 'store'],
    'GET  products/featured'     => [ProductController::class, 'featured'],
    'GET  products/new-arrivals' => [ProductController::class, 'newArrivals'],
    'GET  products/best-sellers' => [ProductController::class, 'bestSellers'],
    'GET  products/filters'      => [ProductController::class, 'filters'],
    'GET  products/{id}'         => [ProductController::class, 'show'],
    'PUT  products/{id}'         => [ProductController::class, 'update'],
    'DELETE products/{id}'       => [ProductController::class, 'destroy'],
    'GET  products/{id}/related' => [ProductController::class, 'related'],
    'GET  products/{id}/reviews' => [ProductController::class, 'reviews'],
    'POST products/{id}/reviews' => [ProductController::class, 'storeReview'],

    // ---- Catégories --------------------------------------------------
    'GET  categories'            => [CategoryController::class, 'index'],
    'POST categories'            => [CategoryController::class, 'store'],
    'GET  categories/{id}'       => [CategoryController::class, 'show'],
    'PUT  categories/{id}'       => [CategoryController::class, 'update'],
    'DELETE categories/{id}'     => [CategoryController::class, 'destroy'],

    // ---- Panier ------------------------------------------------------
    'GET  cart'                  => [CartController::class, 'index'],
    'POST cart/items'            => [CartController::class, 'store'],
    'PUT  cart/items/{itemId}'   => [CartController::class, 'update'],
    'DELETE cart/items/{itemId}' => [CartController::class, 'destroy'],
    'DELETE cart'                => [CartController::class, 'clear'],
    'POST cart/coupon'           => [CartController::class, 'applyCoupon'],
    'DELETE cart/coupon'         => [CartController::class, 'removeCoupon'],

    // ---- Commandes ---------------------------------------------------
    'POST orders'                => [OrderController::class, 'store'],
    'GET  orders/my-orders'      => [OrderController::class, 'myOrders'],
    'GET  orders/all'            => [OrderController::class, 'all'],
    'GET  orders/{id}'           => [OrderController::class, 'show'],
    'PUT  orders/{id}/cancel'    => [OrderController::class, 'cancel'],
    'PUT  orders/{id}/status'    => [OrderController::class, 'updateStatus'],
    'PUT  orders/{id}/payment'   => [OrderController::class, 'updatePayment'],

    // ---- Avis --------------------------------------------------------
    'GET  reviews/my-reviews'    => [ReviewController::class, 'myReviews'],
    'GET  reviews/pending'       => [ReviewController::class, 'pending'],
    'PUT  reviews/{id}/approve'  => [ReviewController::class, 'approve'],
    'PUT  reviews/{id}/reject'   => [ReviewController::class, 'reject'],
    'DELETE reviews/{id}/mine'   => [ReviewController::class, 'destroyMine'],
    'DELETE reviews/{id}'        => [ReviewController::class, 'destroy'],

    // ---- Favoris -----------------------------------------------------
    'GET  wishlist'              => [WishlistController::class, 'index'],
    'POST wishlist/{productId}'  => [WishlistController::class, 'store'],
    'DELETE wishlist/{productId}'=> [WishlistController::class, 'destroy'],
    'POST wishlist/{productId}/toggle' => [WishlistController::class, 'toggle'],
    'GET  wishlist/{productId}/check'  => [WishlistController::class, 'check'],

    // ---- Adresses ----------------------------------------------------
    'GET  addresses'             => [AddressController::class, 'index'],
    'POST addresses'             => [AddressController::class, 'store'],
    'GET  addresses/{id}'        => [AddressController::class, 'show'],
    'PUT  addresses/{id}'        => [AddressController::class, 'update'],
    'DELETE addresses/{id}'      => [AddressController::class, 'destroy'],
    'PUT  addresses/{id}/default'=> [AddressController::class, 'setDefault'],

    // ---- Codes promo -------------------------------------------------
    'GET  coupons'               => [CouponController::class, 'index'],
    'POST coupons'               => [CouponController::class, 'store'],
    'POST coupons/generate'      => [CouponController::class, 'generate'],
    'GET  coupons/available'     => [CouponController::class, 'available'],
    'GET  coupons/{id}'          => [CouponController::class, 'show'],
    'PUT  coupons/{id}'          => [CouponController::class, 'update'],
    'DELETE coupons/{id}'        => [CouponController::class, 'destroy'],

    // ---- Bannières ---------------------------------------------------
    'GET  promo-banners'         => [PromoBannerController::class, 'index'],
    'POST promo-banners'         => [PromoBannerController::class, 'store'],
    'GET  promo-banners/active'  => [PromoBannerController::class, 'active'],
    'PUT  promo-banners/reorder' => [PromoBannerController::class, 'reorder'],
    'GET  promo-banners/{id}'    => [PromoBannerController::class, 'show'],
    'PUT  promo-banners/{id}'    => [PromoBannerController::class, 'update'],
    'DELETE promo-banners/{id}'  => [PromoBannerController::class, 'destroy'],

    // ---- Notifications -----------------------------------------------
    'GET  notifications/my-notifications' => [NotificationController::class, 'myNotifications'],
    'PUT  notifications/read-all'         => [NotificationController::class, 'markAllAsRead'],
    'GET  notifications/unread-count'     => [NotificationController::class, 'unreadCount'],
    'POST notifications/broadcast'         => [NotificationController::class, 'broadcast'],
    'POST notifications/send/{userId}'    => [NotificationController::class, 'sendToUser'],
    'DELETE notifications/all'            => [NotificationController::class, 'destroyAll'],
    'PUT  notifications/{id}/read'        => [NotificationController::class, 'markAsRead'],
    'DELETE notifications/my-notifications/{id}' => [NotificationController::class, 'destroy'],
];