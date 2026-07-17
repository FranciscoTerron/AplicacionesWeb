<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use App\Support\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(): View
    {
        $products = $this->fetch('products');
        $clients = $this->fetch('clients');
        $shipments = $this->fetch('shipments', 'created_at');

        // Estados normalizados al vocabulario unificado (los docs viejos pueden
        // traer in_process/completed/paid/paymentStatus camelCase).
        $orders = $this->fetch('orders', 'created_at')->map(function ($o) {
            $o['status'] = OrderStatus::of($o);
            $o['payment_status'] = OrderStatus::paymentOf($o);

            return $o;
        });

        $activeProducts = $products->where('active', true)->count();
        $activeClients = $clients->where('active', true)->count();
        $totalOrders = $orders->count();

        // Facturación del mes corriente: pagos acreditados (Mercado Pago) más
        // órdenes de pago presencial (efectivo) que el negocio ya aceptó.
        $currentMonth = now()->format('Y-m');
        $monthlyRevenue = $orders
            ->filter(function ($o) use ($currentMonth) {
                if (! str_starts_with((string) ($o['created_at'] ?? ''), $currentMonth)) {
                    return false;
                }

                if ($o['payment_status'] === OrderStatus::PAYMENT_APPROVED) {
                    return $o['status'] !== OrderStatus::CANCELLED;
                }

                return ($o['payment_method'] ?? $o['paymentMethod'] ?? '') !== 'mercado_pago'
                    && in_array($o['status'], OrderStatus::COMMITTED_STATUSES, true);
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
