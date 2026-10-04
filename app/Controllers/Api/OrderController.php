<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\ApiController;
use App\Models\Order;
use App\Services\OrderService;
use Throwable;

/**
 * /api/orders — commandes du client + gestion administrateur.
 */
final class OrderController extends ApiController
{
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
    }

    /** GET /api/orders/my-orders */
    public function myOrders(): never
    {
        $this->requireAuth();

        $orders = Order::forUserWithItems((int) $this->userId());
        $this->ok(array_map(static fn (Order $order): array => $order->toApi(), $orders));
    }

    /** GET /api/orders/{id} */
    public function show(int $id): never
    {
        $this->requireAuth();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->fail('Commande introuvable.', 404);
        }
        $this->assertOwnership($order);

        $this->ok($order->toApi());
    }

    /** POST /api/orders */
    public function store(): never
    {
        $this->requireAuth();

        $payload = $this->payload();
        $shippingAddress = $payload['shippingAddress'] ?? $payload['shipping_address'] ?? [];
        $addressId = $payload['addressId'] ?? $payload['address_id'] ?? null;

        if (!is_array($shippingAddress) && !is_numeric($addressId)) {
            $this->fail('Adresse de livraison manquante.', 422);
        }

        try {
            $order = $this->orderService->place(
                (int) $this->userId(),
                is_array($shippingAddress) ? $shippingAddress : [],
                (string) ($payload['paymentMethod'] ?? $payload['payment_method'] ?? 'CARD'),
                $payload['notes'] ?? null,
                $addressId !== null ? (int) $addressId : null
            );
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 400);
        }

        $this->created($order->toApi(), 'Commande créée avec succès');
    }

    /** PUT /api/orders/{id}/cancel */
    public function cancel(int $id): never
    {
        $this->requireAuth();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->fail('Commande introuvable.', 404);
        }
        $this->assertOwnership($order);

        try {
            $order = $this->orderService->cancel($order, (int) $this->userId());
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 400);
        }

        $this->ok($order->toApi(), 'Commande annulée');
    }

    /** GET /api/orders/all (admin) */
    public function all(): never
    {
        $this->requireAdmin();

        $orders = Order::allOrdered('created_at DESC');
        Order::loadItemsFor($orders);

        $this->ok(array_map(static fn (Order $order): array => $order->toApi(), $orders));
    }

    /** PUT /api/orders/{id}/status (admin) */
    public function updateStatus(int $id): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload(['status' => 'required|max:30']);

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->fail('Commande introuvable.', 404);
        }

        try {
            $order = $this->orderService->changeStatus(
                $order,
                (string) $data['status'],
                $this->request->input('note'),
                $this->request->input('trackingNumber')
            );
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), 400);
        }

        $this->ok($order->toApi(), 'Statut de la commande mis à jour');
    }

    /** PUT /api/orders/{id}/payment (admin) */
    public function updatePayment(int $id): never
    {
        $this->requireAdmin();

        $data = $this->validatePayload(['isPaid' => 'required|boolean']);

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->fail('Commande introuvable.', 404);
        }

        $order = $this->orderService->updatePayment($order, (bool) $data['isPaid']);
        $this->ok($order->toApi(), 'Statut de paiement mis à jour');
    }

    private function assertOwnership(Order $order): void
    {
        if (\App\Core\Auth::isAdmin()) {
            return;
        }
        if ((int) $order->user_id !== $this->userId()) {
            throw \App\Core\HttpException::forbidden('Cette commande ne vous appartient pas.');
        }
    }
}