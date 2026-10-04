<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Services\CartService;
use Throwable;

/**
 * /api/cart — panier du client connecté.
 */
final class CartController extends ApiController
{
    private CartService $cartService;

    public function __construct()
    {
        parent::__construct();
        $this->cartService = new CartService();
    }

    /** GET /api/cart */
    public function index(): never
    {
        $this->requireAuth();
        $this->ok($this->cartService->cart((int) $this->userId())->toApi());
    }

    /** POST /api/cart/items */
    public function store(): never
    {
        $this->requireAuth();

        $data = $this->validatePayload([
            'productId' => 'required|integer',
            'quantity'  => 'required|integer',
        ]);

        try {
            $cart = $this->cartService->add(
                (int) $this->userId(),
                (int) $data['productId'],
                (int) $data['quantity'],
                $this->request->input('selectedSize'),
                $this->request->input('selectedColor')
            );
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 400);
        }

        $this->created($cart->toApi(), 'Article ajouté au panier');
    }

    /** PUT /api/cart/items/{itemId} */
    public function update(int $itemId): never
    {
        $this->requireAuth();

        $data = $this->validatePayload(['quantity' => 'required|integer']);

        $cart = $this->cartService->update((int) $this->userId(), $itemId, (int) $data['quantity']);
        $this->ok($cart->toApi(), 'Panier mis à jour');
    }

    /** DELETE /api/cart/items/{itemId} */
    public function destroy(int $itemId): never
    {
        $this->requireAuth();

        $cart = $this->cartService->remove((int) $this->userId(), $itemId);
        $this->ok($cart->toApi(), 'Article retiré du panier');
    }

    /** DELETE /api/cart */
    public function clear(): never
    {
        $this->requireAuth();

        $cart = $this->cartService->clear((int) $this->userId());
        $this->ok($cart->toApi(), 'Panier vidé');
    }

    /** POST /api/cart/coupon */
    public function applyCoupon(): never
    {
        $this->requireAuth();

        $data = $this->validatePayload(['code' => 'required|max:60']);

        try {
            $cart = $this->cartService->applyCoupon((int) $this->userId(), (string) $data['code']);
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 400);
        }

        $this->ok($cart->toApi(), 'Code promo appliqué');
    }

    /** DELETE /api/cart/coupon */
    public function removeCoupon(): never
    {
        $this->requireAuth();

        $cart = $this->cartService->removeCoupon((int) $this->userId());
        $this->ok($cart->toApi(), 'Code promo retiré');
    }
}