<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Throwable;

/**
 * Back-office : suivi des commandes.
 */
final class OrderController extends Controller
{
    private OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
    }

    public function index(): never
    {
        $this->requireAdmin();

        $page = max(1, $this->request->int('page', 1));
        $filters = [
            'status'  => (string) $this->request->input('statut', ''),
            'keyword' => trim((string) $this->request->input('q', '')),
            'isPaid'  => $this->request->input('paiement', ''),
            'from'    => (string) $this->request->input('du', ''),
            'to'      => (string) $this->request->input('au', ''),
        ];

        $paginator = Order::paginateOrders($filters, $page - 1, 20);
        $paginator->page = $page - 1;

        $this->view('admin/orders/index', [
            'title'     => 'Commandes',
            'paginator' => $paginator,
            'filters'   => $filters,
            'statuses'  => order_statuses(),
        ], 'layouts/admin');
    }

    public function show(int $id): never
    {
        $this->requireAdmin();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }

        $this->view('admin/orders/show', [
            'title'   => 'Commande ' . $order->order_number,
            'order'   => $order,
            'history' => $order->history(),
        ], 'layouts/admin');
    }

    public function updateStatus(int $id): never
    {
        $this->requireAdmin();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }

        $status = strtoupper((string) $this->request->input('status', ''));
        $redirect = '/admin/commandes/' . $id;

        try {
            $this->orderService->changeStatus(
                $order,
                $status,
                $this->request->input('note'),
                $this->request->input('tracking_number')
            );
        } catch (Throwable $e) {
            $this->failure($e->getMessage(), $redirect);
        }

        $this->success('Statut mis à jour : ' . order_status_label($status), $redirect);
    }

    public function updatePayment(int $id): never
    {
        $this->requireAdmin();

        $order = Order::findWithItems($id);
        if ($order === null) {
            $this->abort('Commande introuvable', 404);
        }

        $redirect = '/admin/commandes/' . $id;
        $this->orderService->updatePayment($order, (bool) $this->request->input('is_paid'));

        $this->success('Statut de paiement mis à jour.', $redirect);
    }
}