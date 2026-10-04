<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Services\CartService;
use Throwable;

/**
 * Panier (boutique web).
 */
final class CartController extends Controller
{
    private CartService $cartService;

    public function __construct()
    {
        parent::__construct();
        $this->cartService = new CartService();
    }

    public function index(): never
    {
        $this->requireAuth();

        $cart = $this->cartService->cart((int) $this->userId());

        $this->view('web/cart', [
            'title'    => 'Mon panier',
            'cart'     => $cart,
            'summary'  => $this->cartService->summary($cart),
            'coupons'  => Coupon::availableFor((int) $this->userId()),
        ]);
    }

    public function store(): never
    {
        $this->requireAuth();

        $productId = $this->request->int('product_id', 0);
        $quantity = max(1, $this->request->int('quantity', 1));

        try {
            $this->cartService->add(
                (int) $this->userId(),
                $productId,
                $quantity,
                $this->request->input('taille'),
                $this->request->input('couleur')
            );
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), $this->refererProduct($productId));
        }

        if ($this->request->bool('acheter')) {
            $this->success('Article ajouté, finalisation de la commande.', '/commande');
        }

        $this->success('Article ajouté à votre panier.', '/panier');
    }

    public function update(int $itemId): never
    {
        $this->requireAuth();

        $quantity = max(1, $this->request->int('quantity', 1));
        $this->cartService->update((int) $this->userId(), $itemId, $quantity);

        $this->success('Quantité mise à jour.', '/panier');
    }

    public function destroy(int $itemId): never
    {
        $this->requireAuth();

        $this->cartService->remove((int) $this->userId(), $itemId);
        $this->success('Article retiré du panier.', '/panier');
    }

    public function clear(): never
    {
        $this->requireAuth();

        $this->cartService->clear((int) $this->userId());
        $this->success('Panier vidé.', '/panier');
    }

    public function applyCoupon(): never
    {
        $this->requireAuth();

        $code = trim((string) $this->request->input('code', ''));
        if ($code === '') {
            $this->failure('Saisissez un code promo.', '/panier');
        }

        try {
            $this->cartService->applyCoupon((int) $this->userId(), $code);
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/panier');
        }

        $this->success('Code promo appliqué : ' . mb_strtoupper($code), '/panier');
    }

    public function removeCoupon(): never
    {
        $this->requireAuth();

        $this->cartService->removeCoupon((int) $this->userId());
        $this->success('Code promo retiré.', '/panier');
    }

    private function refererProduct(int $productId): string
    {
        return $productId > 0 ? '/produit/' . $productId : '/boutique';
    }
}