<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Paginator;
use App\Models\Order;
use App\Models\User;
use App\Services\SalesService;

/**
 * Back-office : clients et statistiques associées.
 */
final class CustomerController extends Controller
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

        $keyword = trim((string) $this->request->input('q', ''));
        $page = max(1, $this->request->int('page', 1));

        $paginator = User::paginateUsers($keyword, $page - 1, 20);
        $paginator->page = $page - 1;

        $this->view('admin/customers/index', [
            'title'     => 'Clients',
            'paginator' => $paginator,
            'keyword'   => $keyword,
            'stats'     => $this->sales->customerStats(8),
            'totals'    => [
                'customers' => User::countCustomers(),
                'orders'    => Order::countAll(),
            ],
        ], 'layouts/admin');
    }

    public function show(int $id): never
    {
        $this->requireAdmin();

        $user = User::find($id);
        if ($user === null) {
            $this->abort('Client introuvable', 404);
        }

        $orders = Order::forUser($id);
        Order::loadItemsFor($orders);

        $this->view('admin/customers/show', [
            'title'   => 'Client ' . $user->fullName(),
            'user'    => $user,
            'orders'  => $orders,
            'stats'   => [
                'orders' => $user->orderCount(),
                'spent'  => $user->totalSpent(),
            ],
        ], 'layouts/admin');
    }
}