<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Paginator;
use InvalidArgumentException;

/**
 * Commande client.
 */
final class Order extends Model
{
    protected static string $table = 'orders';
    protected static string $primaryKey = 'id';

    protected static array $fillable = [
        'order_number', 'user_id', 'customer_email', 'subtotal', 'shipping_fee', 'discount_amount',
        'total', 'status', 'is_paid', 'payment_method', 'tracking_number', 'notes',
        'ship_full_name', 'ship_phone', 'ship_address', 'ship_city', 'ship_state', 'ship_zip_code', 'ship_country',
    ];

    protected static array $casts = [
        'id'              => 'int',
        'user_id'         => 'int',
        'subtotal'        => 'float',
        'shipping_fee'    => 'float',
        'discount_amount' => 'float',
        'total'           => 'float',
        'is_paid'         => 'bool',
    ];

    public const STATUSES = [
        'PENDING', 'CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED', 'CANCELLED', 'REFUNDED',
    ];

    /** @var array<int, OrderItem> */
    public array $items = [];

    // -----------------------------------------------------------------
    // Requêtes
    // -----------------------------------------------------------------

    public static function nextNumber(): string
    {
        $year = date('Y');
        $last = self::value(
            "SELECT order_number FROM orders WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1",
            ['prefix' => "ORD-{$year}-%"],
            null
        );

        $sequence = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches) === 1) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('ORD-%s-%04d', $year, $sequence);
    }

    /** @return array<int, self> */
    public static function forUser(int $userId): array
    {
        return self::rows(
            'SELECT * FROM orders WHERE user_id = :userId ORDER BY created_at DESC',
            ['userId' => $userId]
        );
    }

    /** @return array<int, self> */
    public static function forUserWithItems(int $userId): array
    {
        $orders = self::forUser($userId);
        self::loadItemsFor($orders);

        return $orders;
    }

    /** @return array<int, self> */
    public static function allOrdered(string $orderBy = 'created_at DESC'): array
    {
        return self::rows('SELECT * FROM orders ORDER BY ' . $orderBy);
    }

    public static function findWithItems(int $id): ?self
    {
        $order = self::find($id);

        return $order?->loadItems();
    }

    /**
     * Pagination côté administration.
     *
     * @param array<string, mixed> $filters status, keyword, userId, from, to
     */
    public static function paginateOrders(array $filters = [], int $page = 0, int $size = 20): Paginator
    {
        [$where, $params] = self::buildOrderFilters($filters);

        $total = (int) self::value('SELECT COUNT(*) FROM orders o' . $where, $params, 0);
        $status = strtoupper((string) ($filters['status'] ?? ''));

        $items = self::rows(
            'SELECT o.* FROM orders o' . $where . ' ORDER BY o.created_at DESC LIMIT ' . (int) $size
            . ' OFFSET ' . (int) ($page * $size),
            $params
        );

        self::loadItemsFor($items);

        return new Paginator($items, $total, $page, $size);
    }

    /**
     * @param  array<string, mixed> $filters
     * @return array{0: string, 1: array<string, mixed>}
     */
    public static function buildOrderFilters(array $filters): array
    {
        $clauses = [];
        $params = [];

        $status = strtoupper(trim((string) ($filters['status'] ?? '')));
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $clauses[] = 'o.status = :status';
            $params['status'] = $status;
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $kw = '%' . $keyword . '%';
            $clauses[] = '(o.order_number LIKE :kw1 OR o.customer_email LIKE :kw2 OR o.ship_full_name LIKE :kw3)';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
        }

        if (!empty($filters['userId'])) {
            $clauses[] = 'o.user_id = :userId';
            $params['userId'] = (int) $filters['userId'];
        }
        if (!empty($filters['isPaid'])) {
            $clauses[] = 'o.is_paid = :isPaid';
            $params['isPaid'] = (int) (bool) $filters['isPaid'];
        }
        if (!empty($filters['from'])) {
            $clauses[] = 'o.created_at >= :from';
            $params['from'] = date('Y-m-d H:i:s', (int) strtotime((string) $filters['from']));
        }
        if (!empty($filters['to'])) {
            $clauses[] = 'o.created_at <= :to';
            $params['to'] = date('Y-m-d H:i:s 23:59:59', (int) strtotime((string) $filters['to']));
        }

        return [$clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses), $params];
    }

    /**
     * Charge les articles de plusieurs commandes en 2 requêtes.
     *
     * @param array<int, self> $orders
     */
    public static function loadItemsFor(array $orders): void
    {
        if ($orders === []) {
            return;
        }

        $ids = array_map(static fn (self $order): int => (int) $order->id, $orders);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = self::db()->prepare('SELECT * FROM order_items WHERE order_id IN (' . $placeholders . ')');
        $statement->execute($ids);
        $rows = $statement->fetchAll();

        $itemsByOrder = [];
        foreach ($rows as $row) {
            $item = new OrderItem();
            $item->hydrate($row);
            $itemsByOrder[(int) $row['order_id']][] = $item;
        }

        foreach ($orders as $order) {
            $order->items = $itemsByOrder[(int) $order->id] ?? [];
        }
    }

    public function loadItems(): self
    {
        $rows = self::db()->query('SELECT * FROM order_items WHERE order_id = ' . (int) $this->id . ' ORDER BY id ASC')
            ->fetchAll();

        $this->items = [];
        foreach ($rows as $row) {
            $item = new OrderItem();
            $item->hydrate($row);
            $this->items[] = $item;
        }

        return $this;
    }

    public function addItemFromCart(CartItem $cartItem, Product $product): void
    {
        $item = new OrderItem();
        $item->fill([
            'order_id'          => (int) $this->id,
            'product_id'        => (int) $product->id,
            'product_name'      => (string) $product->name,
            'product_thumbnail' => $product->thumbnail,
            'quantity'          => (int) $cartItem->quantity,
            'unit_price'        => $product->effectivePrice(),
            'selected_size'     => $cartItem->selected_size,
            'selected_color'    => $cartItem->selected_color,
        ]);
        $item->save();

        $this->items[] = $item;
    }

    // -----------------------------------------------------------------
    // Cycle de vie
    // -----------------------------------------------------------------

    public function setStatus(string $status, ?string $note = null): void
    {
        $status = strtoupper($status);
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Statut de commande invalide : ' . $status);
        }

        $this->set('status', $status);
        $this->save();

        self::db()->exec(
            'INSERT INTO order_status_history (order_id, status, note) VALUES ('
            . (int) $this->id . ', ' . self::db()->quote($status) . ', ' . self::db()->quote($note) . ')'
        );
    }

    public function markPaid(bool $paid = true): void
    {
        $this->set('is_paid', $paid);
        $this->save();
    }

    public function canBeCancelled(): bool
    {
        return in_array(strtoupper((string) $this->status), ['PENDING', 'CONFIRMED', 'PROCESSING'], true);
    }

    public function customer(): ?User
    {
        return $this->user_id === null ? null : User::find((int) $this->user_id);
    }

    public function customerName(): string
    {
        if (!empty($this->ship_full_name)) {
            return (string) $this->ship_full_name;
        }
        $customer = $this->customer();

        return $customer?->fullName() ?? (string) ($this->customer_email ?? 'Client');
    }

    public function statusLabel(): string
    {
        return order_status_label($this->status);
    }

    public function statusBadge(): string
    {
        return order_status_badge($this->status);
    }

    /** @return array<int, array<string, mixed>> */
    public function history(): array
    {
        return self::db()
            ->query('SELECT * FROM order_status_history WHERE order_id = ' . (int) $this->id . ' ORDER BY created_at ASC')
            ->fetchAll();
    }

    /** @return array<string, mixed> Adresse imbriquée attendue par le client mobile. */
    public function shippingAddress(): array
    {
        return [
            'fullName' => $this->ship_full_name,
            'address'  => $this->ship_address,
            'city'     => $this->ship_city,
            'state'    => $this->ship_state,
            'zipCode'  => $this->ship_zip_code,
            'country'  => $this->ship_country,
            'phone'    => $this->ship_phone,
        ];
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        if ($this->items === []) {
            $this->loadItems();
        }

        return [
            'id'              => (int) $this->id,
            'orderNumber'     => (string) $this->order_number,
            'items'           => array_map(static fn (OrderItem $item): array => $item->toApi(), $this->items),
            'subtotal'        => (float) $this->subtotal,
            'shippingFee'     => (float) $this->shipping_fee,
            'discountAmount'  => (float) $this->discount_amount,
            'total'           => (float) $this->total,
            'status'          => strtoupper((string) $this->status),
            'isPaid'          => (bool) $this->is_paid,
            'shippingAddress' => $this->shippingAddress(),
            'paymentMethod'   => $this->payment_method,
            'trackingNumber'  => $this->tracking_number,
            'notes'           => $this->notes,
            'customerEmail'   => $this->customer_email,
            'userId'          => $this->user_id === null ? null : (int) $this->user_id,
            'createdAt'       => iso_date($this->created_at),
            'updatedAt'       => iso_date($this->updated_at),
        ];
    }
}
