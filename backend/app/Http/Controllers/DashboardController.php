<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(): View
    {
        $products = $this->fetch('products');
        $clients = $this->fetch('clients');
        $orders = $this->fetch('orders', 'created_at');
        $shipments = $this->fetch('shipments', 'created_at');

        $activeProducts = $products->where('active', true)->count();
        $activeClients = $clients->where('active', true)->count();
        $totalOrders = $orders->count();

        $monthlyRevenue = $orders
            ->filter(function ($o) {
                $status = $o['status'] ?? '';
                $payment = $o['paymentStatus'] ?? $o['payment_status'] ?? '';

                return in_array($status, ['completed', 'in_process']) || $payment === 'paid';
            })
            ->sum(function ($o) {
                return (float) ($o['total_amount'] ?? $o['total'] ?? 0);
            });

        $ordersByStatus = $orders->groupBy('status')->map->count();

        $shipmentsInTransit = $shipments->where('status', 'in_transit')->count();

        $recentOrders = $orders
            ->sortByDesc('created_at')
            ->take(5)
            ->values();

        return view('admin.dashboard', [
            'kpis' => [
                'activeProducts' => $activeProducts,
                'activeClients' => $activeClients,
                'totalOrders' => $totalOrders,
                'monthlyRevenue' => $monthlyRevenue,
                'shipmentsInTransit' => $shipmentsInTransit,
            ],
            'ordersByStatus' => $ordersByStatus,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function fetch(string $collection, string $orderBy = 'name'): Collection
    {
        $result = $this->firestore->listDocuments($collection, 200, null, $orderBy);

        return collect($result['documents'] ?? []);
    }
}
