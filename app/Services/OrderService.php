<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use RuntimeException;

/**
 * Passage de commande : transaction (commande + stock + panier + notification).
 */
final class OrderService
{
    public const PAYMENT_METHODS = ['CARD', 'CASH', 'TRANSFER', 'PAYPAL'];

    /**
     * @param array<string, mixed> $shippingAddress
     */
    public function place(
        int $userId,
        array $shippingAddress,
        string $paymentMethod = 'CARD',
        ?string $notes = null,
        ?int $addressId = null
    ): Order {
        $user = User::find($userId);
        if ($user === null) {
            throw new RuntimeException('Utilisateur introuvable.');
        }

        $shippingAddress = $this->normalizeAddress($shippingAddress, $addressId, $userId);
        $paymentMethod = $this->normalizePaymentMethod($paymentMethod);
        $cart = Cart::forUser($userId);

        if ($cart->isEmpty()) {
            throw new RuntimeException('Votre panier est vide.');
        }

        $this->assertStockAvailable($cart);

        $order = new Order();
        $order->fill([
            'order_number'     => Order::nextNumber(),
            'user_id'          => $userId,
            'customer_email'   => $user->email,
            'subtotal'         => $cart->subtotal(),
            'shipping_fee'     => $cart->shipping(),
            'discount_amount'  => $cart->discount(),
            'total'            => $cart->total(),
            'status'           => 'PENDING',
            'is_paid'          => false,
            'payment_method'   => $paymentMethod,
            'notes'            => $notes,
            'ship_full_name'   => $shippingAddress['fullName'],
            'ship_phone'       => $shippingAddress['phone'] ?? null,
            'ship_address'     => $shippingAddress['address'],
            'ship_city'        => $shippingAddress['city'],
            'ship_state'       => $shippingAddress['state'] ?? null,
            'ship_zip_code'    => $shippingAddress['zipCode'] ?? null,
            'ship_country'     => $shippingAddress['country'] ?? 'Maroc',
        ]);
        $order->save();

        foreach ($cart->items as $item) {
            $product = $item->product();
            if ($product === null) {
                throw new RuntimeException('Un article du panier n\'existe plus.');
            }
            $order->addItemFromCart($item, $product);
            $product->decreaseStock((int) $item->quantity);
        }

        if (!empty($cart->coupon_code)) {
            $coupon = \App\Models\Coupon::findByCode((string) $cart->coupon_code);
            $coupon?->incrementUsage();
        }

        $cart->clear();
        $order->setStatus('PENDING', 'Commande reçue');

        \App\Models\Notification::notify(
            $userId,
            'ORDER_RECEIVED',
            'Commande reçue ' . $order->order_number,
            'Merci pour votre commande. Nous la préparons dans les meilleurs délais.',
            'HIGH',
            ['orderId' => (int) $order->id, 'orderNumber' => (string) $order->order_number]
        );

        return $order->loadItems();
    }

    public function changeStatus(Order $order, string $status, ?string $note = null, ?string $trackingNumber = null): Order
    {
        $previous = strtoupper((string) $order->status);
        $order->setStatus($status, $note);

        if ($trackingNumber !== null && $trackingNumber !== '') {
            $order->tracking_number = $trackingNumber;
            $order->save();
        }

        if (in_array(strtoupper($status), ['CANCELLED', 'REFUNDED'], true) && $previous !== strtoupper($status)) {
            $this->restoreStock($order);
        }

        $userId = $order->user_id === null ? null : (int) $order->user_id;
        if ($userId !== null) {
            \App\Models\Notification::notify(
                $userId,
                $this->notificationTypeFor((string) $status),
                'Commande ' . strtolower(order_status_label((string) $status)) . ' ' . $order->order_number,
                $note ?? ('Votre commande est désormais : ' . order_status_label((string) $status)),
                'HIGH',
                ['orderId' => (int) $order->id]
            );
        }

        return $order->loadItems();
    }

    public function cancel(Order $order, ?int $requestingUserId = null): Order
    {
        if (!$order->canBeCancelled()) {
            throw new RuntimeException('Cette commande ne peut plus être annulée.');
        }
        if ($requestingUserId !== null && (int) $order->user_id !== $requestingUserId) {
            throw new RuntimeException('Vous n\'êtes pas autorisé à annuler cette commande.');
        }

        return $this->changeStatus($order, 'CANCELLED', 'Annulée par le client');
    }

    public function updatePayment(Order $order, bool $isPaid): Order
    {
        $order->markPaid($isPaid);

        $userId = $order->user_id === null ? null : (int) $order->user_id;
        if ($userId !== null) {
            \App\Models\Notification::notify(
                $userId,
                $isPaid ? 'PAYMENT_SUCCESS' : 'PAYMENT_FAILED',
                $isPaid ? 'Paiement confirmé' : 'Paiement en attente',
                'Commande ' . $order->order_number . ($isPaid ? ' : paiement enregistré.' : ' : le paiement n\'est pas encore confirmé.'),
                'MEDIUM',
                ['orderId' => (int) $order->id]
            );
        }

        return $order->loadItems();
    }

    private function assertStockAvailable(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $product = $item->product();
            if ($product === null) {
                throw new RuntimeException('Un article de votre panier n\'est plus disponible.');
            }
            if ((int) $product->stock < (int) $item->quantity) {
                throw new RuntimeException(sprintf(
                    'Stock insuffisant pour « %s » (%d restant(s)).',
                    (string) $product->name,
                    (int) $product->stock
                ));
            }
        }
    }

    private function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }
            $product = Product::find((int) $item->product_id);
            $product?->increaseStock((int) $item->quantity);
        }
    }

    /**
     * @param  array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function normalizeAddress(array $input, ?int $addressId, int $userId): array
    {
        if ($addressId !== null) {
            $address = Address::findForUser($addressId, $userId);
            if ($address === null) {
                throw new RuntimeException('Adresse de livraison introuvable.');
            }

            return $address->toShippingAddress();
        }

        $required = ['fullName', 'address', 'city', 'country'];
        foreach ($required as $field) {
            if (trim((string) ($input[$field] ?? '')) === '') {
                throw new RuntimeException('Le champ « ' . $field . ' » est obligatoire pour la livraison.');
            }
        }

        return [
            'fullName' => trim((string) $input['fullName']),
            'address'  => trim((string) $input['address']),
            'city'     => trim((string) $input['city']),
            'state'    => $input['state'] ?? null,
            'zipCode'  => $input['zipCode'] ?? $input['zip_code'] ?? null,
            'country'  => trim((string) $input['country']),
            'phone'    => $input['phone'] ?? null,
        ];
    }

    private function normalizePaymentMethod(string $method): string
    {
        $method = strtoupper(trim($method));
        if ($method === '') {
            return 'CARD';
        }

        return in_array($method, self::PAYMENT_METHODS, true) ? $method : 'CARD';
    }

    private function notificationTypeFor(string $status): string
    {
        return match (strtoupper($status)) {
            'CONFIRMED'  => 'ORDER_CONFIRMED',
            'PROCESSING' => 'ORDER_CONFIRMED',
            'SHIPPED'    => 'ORDER_SHIPPED',
            'DELIVERED'  => 'ORDER_DELIVERED',
            'CANCELLED'  => 'ORDER_CANCELLED',
            'REFUNDED'   => 'ORDER_REFUNDED',
            default      => 'ORDER_RECEIVED',
        };
    }
}
