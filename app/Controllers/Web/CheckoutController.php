<?php
declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Throwable;

/**
 * Tunnel de commande.
 */
final class CheckoutController extends Controller
{
    private CartService $cartService;
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->cartService = new CartService();
        $this->orderService = new OrderService();
    }

    public function index(): never
    {
        $this->requireAuth();

        $cart = $this->cartService->cart((int) $this->userId());
        if ($cart->isEmpty()) {
            Session::error('Votre panier est vide.');
            $this->redirect('/boutique');
        }

        $this->view('web/checkout', [
            'title'     => 'Finaliser la commande',
            'cart'      => $cart,
            'summary'   => $this->cartService->summary($cart),
            'addresses' => Address::forUser((int) $this->userId()),
            'coupons'   => Coupon::availableFor((int) $this->userId()),
            'methods'   => OrderService::PAYMENT_METHODS,
        ]);
    }

    public function store(): never
    {
        $this->requireAuth();

        $payload = $this->request->all();
        $savedAddressId = isset($payload['adresse_id']) && $payload['adresse_id'] !== ''
            ? (int) $payload['adresse_id']
            : null;

        $shippingAddress = [
            'fullName' => trim((string) ($payload['fullName'] ?? '')),
            'address'  => trim((string) ($payload['address'] ?? '')),
            'city'     => trim((string) ($payload['city'] ?? '')),
            'state'    => trim((string) ($payload['state'] ?? '')),
            'zipCode'  => trim((string) ($payload['zipCode'] ?? '')),
            'country'  => trim((string) ($payload['country'] ?? '')),
            'phone'    => trim((string) ($payload['phone'] ?? '')),
        ];

        $validator = Validator::make($payload, [
            'fullName' => 'required|max:160',
            'address'  => 'required|max:255',
            'city'     => 'required|max:120',
            'country'  => 'required|max:120',
            'phone'    => 'required|max:40',
        ]);

        if ($savedAddressId === null && $validator->fails()) {
            foreach ($validator->errors() as $message) {
                Session::error($message);
            }
            $this->withInput($payload);
            $this->redirect('/commande');
        }

        if ($savedAddressId !== null && Address::findForUser($savedAddressId, (int) $this->userId()) === null) {
            Session::error('Adresse sélectionnée introuvable.');
            $this->redirect('/commande');
        }

        try {
            $order = $this->orderService->place(
                (int) $this->userId(),
                $shippingAddress,
                (string) ($payload['paymentMethod'] ?? 'CARD'),
                $payload['notes'] ?? null,
                $savedAddressId
            );
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), '/commande');
        }

        $this->success('Commande ' . $order->order_number . ' enregistrée.', '/commande/succes/' . (int) $order->id);
    }

    public function confirmation(int $id): never
    {
        $this->requireAuth();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }
        if (!Auth::isAdmin() && (int) $order->user_id !== $this->userId()) {
            $this->abort('Cette commande ne vous appartient pas', 403);
        }

        $this->view('web/order-success', [
            'title' => 'Commande confirmée',
            'order' => $order,
        ]);
    }
}