<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\SalesService;

/**
 * Tableau de bord et rapports de ventes.
 */
final class DashboardController extends Controller
{
    private SalesService $sales;

    public function __construct()
    {
        parent::__construct();
        $this->sales = new SalesService();
    }

    public function index(): never
    {
        $this->requireAdmin();

        $this->view('admin/dashboard', [
            'title'          => 'Tableau de bord',
            'stats'          => $this->sales->dashboard(),
            'monthly'        => $this->sales->monthlySales(12),
            'byCategory'     => $this->sales->salesByCategory(),
            'topProducts'    => $this->sales->topProducts(5),
            'recentOrders'   => $this->sales->recentOrders(6),
            'lowStock'       => $this->sales->lowStockProducts(6),
            'pendingReviews' => $this->sales->pendingReviews(5),
            'statusCounts'   => $this->sales->statusBreakdown(),
        ], 'layouts/admin');
    }

    public function sales(): never
    {
        $this->requireAdmin();

        $customers = $this->sales->customerStats(20);

        $this->view('admin/sales', [
            'title'         => 'Statistiques de ventes',
            'stats'         => $this->sales->dashboard(),
            'monthly'       => $this->sales->monthlySales(12),
            'byCategory'    => $this->sales->salesByCategory(),
            'topProducts'   => $this->sales->topProducts(15),
            'customers'     => $customers,
            'statusCounts'  => $this->sales->statusBreakdown(),
            'bestSeller'    => Product::topSelling(1)[0] ?? null,
            'totalOrders'   => Order::countAll(),
        ], 'layouts/admin');
    }
}