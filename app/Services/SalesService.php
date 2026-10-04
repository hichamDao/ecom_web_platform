<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

/**
 * Statistiques de vente pour le tableau de bord et les rapports.
 */
final class SalesService
{
    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr', 5 => 'Mai', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aoû', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
    ];

    /** Commandes "facturées" : payées et non annulées/remboursées. */
    public const PAID_STATUSES = "('SHIPPED','DELIVERED','PROCESSING','CONFIRMED')";

    /** @return array<string, mixed> */
    public function dashboard(): array
    {
        $db = Database::instance();

        $revenue = (float) $db->fetchValue(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE is_paid = 1 AND status IN " . self::PAID_STATUSES,
            [],
            0
        );
        $orderCount = (int) $db->fetchValue('SELECT COUNT(*) FROM orders', [], 0);
        $paidOrders = (int) $db->fetchValue(
            'SELECT COUNT(*) FROM orders WHERE is_paid = 1 AND status IN ' . self::PAID_STATUSES,
            [],
            0
        );
        $productCount = (int) $db->fetchValue('SELECT COUNT(*) FROM products', [], 0);
        $customerCount = (int) $db->fetchValue("SELECT COUNT(*) FROM users WHERE role = 'CUSTOMER'", [], 0);
        $pendingOrders = (int) $db->fetchValue("SELECT COUNT(*) FROM orders WHERE status = 'PENDING'", [], 0);
        $pendingReviews = (int) $db->fetchValue("SELECT COUNT(*) FROM reviews WHERE status = 'PENDING'", [], 0);
        $lowStock = (int) $db->fetchValue(
            'SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= :threshold',
            ['threshold' => low_stock_threshold()],
            0
        );
        $unitsSold = (int) $db->fetchValue(
            'SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE o.is_paid = 1 AND o.status IN ' . self::PAID_STATUSES,
            [],
            0
        );
        $averageOrder = $paidOrders > 0 ? round($revenue / $paidOrders, 2) : 0.0;
        $newCustomers = (int) $db->fetchValue(
            'SELECT COUNT(*) FROM users WHERE role = \'CUSTOMER\' AND created_at >= :date',
            ['date' => date('Y-m-d H:i:s', strtotime('-30 days'))],
            0
        );

        return [
            'revenue'        => $revenue,
            'orderCount'     => $orderCount,
            'paidOrders'     => $paidOrders,
            'averageOrder'   => $averageOrder,
            'unitsSold'      => $unitsSold,
            'productCount'   => $productCount,
            'customerCount'  => $customerCount,
            'newCustomers'   => $newCustomers,
            'pendingOrders'  => $pendingOrders,
            'pendingReviews' => $pendingReviews,
            'lowStock'       => $lowStock,
        ];
    }

    /**
     * Chiffre d'affaires mensuel des 12 derniers mois.
     *
     * @return array<int, array<string, mixed>>
     */
    public function monthlySales(int $months = 12): array
    {
        $db = Database::instance();
        $start = (new \DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');

        $rows = $db->fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS period,
                    COALESCE(SUM(total), 0) AS revenue,
                    COUNT(*) AS orders
             FROM orders
             WHERE is_paid = 1 AND status IN " . self::PAID_STATUSES . "
               AND created_at >= :start
             GROUP BY period",
            ['start' => $start->format('Y-m-d 00:00:00')]
        );

        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) $row['period']] = [
                'revenue' => (float) $row['revenue'],
                'orders'  => (int) $row['orders'],
            ];
        }

        $result = [];
        for ($i = 0; $i < $months; $i++) {
            $date = $start->modify('+' . $i . ' months');
            $period = $date->format('Y-m');
            $result[] = [
                'month'      => self::MONTH_LABELS[(int) $date->format('n')],
                'monthIndex' => (int) $date->format('n'),
                'period'     => $period,
                'year'       => (int) $date->format('Y'),
                'totalSales' => $totals[$period]['revenue'] ?? 0.0,
                'orderCount' => $totals[$period]['orders'] ?? 0,
            ];
        }

        return $result;
    }

    /**
     * Ventes par catégorie.
     *
     * @return array<int, array<string, mixed>>
     */
    public function salesByCategory(): array
    {
        return Database::instance()->fetchAll(
            'SELECT COALESCE(c.name, \'Autres\') AS categoryName,
                    COALESCE(SUM(oi.quantity), 0) AS totalQuantity,
                    COALESCE(SUM(oi.unit_price * oi.quantity), 0) AS totalRevenue
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             LEFT JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE o.is_paid = 1 AND o.status IN ' . self::PAID_STATUSES . '
             GROUP BY c.name
             ORDER BY totalRevenue DESC'
        );
    }

    /**
     * Produits les plus vendus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topProducts(int $limit = 5): array
    {
        return Database::instance()->fetchAll(
            'SELECT p.id AS productId, p.name AS productName, c.name AS categoryName,
                    COALESCE(SUM(oi.quantity), 0) AS quantitySold,
                    COALESCE(SUM(oi.unit_price * oi.quantity), 0) AS totalRevenue,
                    p.stock AS currentStock
             FROM order_items oi
             INNER JOIN orders o ON o.id = oi.order_id
             INNER JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE o.is_paid = 1 AND o.status IN ' . self::PAID_STATUSES . '
             GROUP BY p.id, p.name, c.name, p.stock
             ORDER BY quantitySold DESC, totalRevenue DESC
             LIMIT ' . (int) $limit
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function statusBreakdown(): array
    {
        return Database::instance()->fetchAll(
            'SELECT status, COUNT(*) AS count FROM orders GROUP BY status ORDER BY count DESC'
        );
    }

    /**
     * Statistiques par client (agrégées depuis les commandes payées).
     *
     * @return array<int, array<string, mixed>>
     */
    public function customerStats(int $limit = 50): array
    {
        $rows = Database::instance()->fetchAll(
            "SELECT COALESCE(NULLIF(o.customer_email, ''), 'unknown') AS email,
                    o.user_id AS userId,
                    COALESCE(u.first_name, '') AS firstName,
                    COALESCE(u.last_name, '') AS lastName,
                    COALESCE(u.username, '') AS username,
                    COUNT(*) AS totalOrders,
                    COALESCE(SUM(CASE WHEN o.is_paid = 1 AND o.status IN " . self::PAID_STATUSES . " THEN o.total ELSE 0 END), 0) AS totalSpent,
                    MIN(o.created_at) AS firstOrderDate,
                    MAX(o.created_at) AS lastOrderDate
             FROM orders o
             LEFT JOIN users u ON u.id = o.user_id
             GROUP BY email, o.user_id, u.first_name, u.last_name, u.username
             ORDER BY totalSpent DESC
             LIMIT " . (int) $limit
        );

        return array_map(static function (array $row): array {
            $name = trim($row['firstName'] . ' ' . $row['lastName']);

            return [
                'email'         => (string) $row['email'],
                'userId'        => $row['userId'] === null ? null : (int) $row['userId'],
                'name'          => $name !== '' ? $name : (string) ($row['username'] ?: null),
                'totalOrders'   => (int) $row['totalOrders'],
                'totalSpent'    => (float) $row['totalSpent'],
                'firstOrderDate' => iso_date($row['firstOrderDate']),
                'lastOrderDate'  => iso_date($row['lastOrderDate']),
            ];
        }, $rows);
    }

    /** @return array<int, Order> */
    public function recentOrders(int $limit = 8): array
    {
        $orders = Order::rows('SELECT * FROM orders ORDER BY created_at DESC LIMIT ' . (int) $limit);
        Order::loadItemsFor($orders);

        return $orders;
    }

    /** @return array<int, Product> */
    public function lowStockProducts(int $limit = 8): array
    {
        return Product::lowStock(low_stock_threshold(), $limit);
    }

    public function pendingReviews(int $limit = 5): array
    {
        return Review::pending($limit);
    }

    public function newCustomers(int $limit = 5): array
    {
        return User::rows(
            "SELECT * FROM users WHERE role = 'CUSTOMER' ORDER BY created_at DESC LIMIT " . (int) $limit
        );
    }
}