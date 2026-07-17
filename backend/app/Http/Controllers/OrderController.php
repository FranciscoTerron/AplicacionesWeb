<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Order;
use App\Services\FirestoreService;
use App\Services\StockService;
use App\Support\OrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Nota: Order NO tiene método activate().
 * El estado de una orden se gestiona vía updateStatus() (ej: pendiente, pagada, enviada, cancelada).
 * Una orden no se "desactiva" como Category/Product/Shipment.
 *
 * UI: Orders usa modal-only (igual que clients/users). Los métodos create/edit/show
 * redirigen al index porque la interacción ocurre dentro del modal del listado.
 */
class OrderController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore, private readonly StockService $stock)
    {
        $this->firestore = $firestore;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route($this->getRedirectRoute());
    }

    public function edit(string $id): RedirectResponse
    {
        return redirect()->route($this->getRedirectRoute());
    }

    public function show(string $id): RedirectResponse
    {
        return redirect()->route($this->getRedirectRoute());
    }

    protected function getCollectionName(): string
    {
        return 'orders';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.orders.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.orders';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreOrderRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateOrderRequest::class;
    }

    protected function getModelClass(): string
    {
        return Order::class;
    }

    public static function statuses(): array
    {
        return OrderStatus::statuses();
    }

    protected function getExtraCreateData(): array
    {
        $result = $this->firestore->listDocuments('clients', 100, null, 'name');
        $clients = collect($result['documents'] ?? []);

        $productsResult = $this->firestore->listDocuments('products', 100);
        $products = collect($productsResult['documents'] ?? [])->where('active', true);

        return [
            'clients' => $clients,
            'products' => $products,
            'statuses' => self::statuses(),
        ];
    }

    protected function getExtraEditData(string $id): array
    {
        return $this->getExtraCreateData();
    }

    public function index(): View
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $page = max(1, intval(request()->get('page', 1)));
        $perPage = intval(request()->get('per_page', 10));
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $statusFilter = request()->get('status');
        $paymentFilter = request()->get('payment_status');

        $fetchResult = $this->firestore->fetchForPage(
            $this->getCollectionName(),
            $perPage,
            $startAfter,
            'created_at',
        );
        // Normalizar estados legacy (in_process, paid, paymentStatus camelCase)
        // al vocabulario unificado: filtros, labels y badges trabajan sobre esto.
        $items = collect($fetchResult['documents'] ?? [])->map(function ($item) {
            $item['status'] = OrderStatus::of($item);
            $item['payment_status'] = OrderStatus::paymentOf($item);
            unset($item['paymentStatus']);

            return $item;
        });

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['clientId'] ?? $item['client_id'] ?? '', $search) !== false
                    || stripos($item['client_name'] ?? '', $search) !== false
                    || stripos($item['id'] ?? '', $search) !== false;
            })->values();
        }

        if ($statusFilter) {
            $items = $items->where('status', $statusFilter)->values();
        }

        if ($paymentFilter) {
            $items = $items->where('payment_status', $paymentFilter)->values();
        }

        // Datos para el modal "new" (selects de cliente/producto, sin paginar — máx. 100).
        $clientsResult = $this->firestore->listDocuments('clients', 100, null, 'name');
        $clients = collect($clientsResult['documents'] ?? [])->where('active', true)->values();

        $productsResult = $this->firestore->listDocuments('products', 100);
        $products = collect($productsResult['documents'] ?? [])->where('active', true)->values();

        // Paginate filtered results
        $totalFiltered = $items->count();
        $totalPages = intval(ceil($totalFiltered / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageItems = $items->slice($offset, $perPage)->values();

        return view("{$this->getViewFolder()}.index", [
            'orders' => $pageItems,
            'clients' => $clients,
            'products' => $products,
            'statuses' => self::statuses(),
            'paymentStatuses' => OrderStatus::paymentStatuses(),
            'search' => $search,
            'statusFilter' => $statusFilter,
            'paymentFilter' => $paymentFilter,
            'hasMore' => $page < $totalPages,
            'lastDocumentId' => $fetchResult['lastDocumentId'],
            'page' => $page,
            'perPage' => $perPage,
            'totalFiltered' => $totalFiltered,
            'totalPages' => $totalPages,
        ]);
    }

    public function updateStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(self::statuses())),
        ]);

        try {
            $order = $this->firestore->getDocument($this->getCollectionName(), $id) ?? [];
            $newStatus = $request->input('status');

            $data = [
                'status' => $newStatus,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];

            // HU-B04: para métodos sin acreditación online (efectivo), el stock
            // se compromete cuando el negocio acepta la orden. Para Mercado Pago
            // lo descuenta el webhook al aprobarse el pago. Idempotente vía
            // stock_decremented, así reconfirmar no descuenta dos veces.
            $paymentMethod = (string) ($order['payment_method'] ?? $order['paymentMethod'] ?? '');
            if (in_array($newStatus, OrderStatus::COMMITTED_STATUSES, true) && $paymentMethod !== 'mercado_pago') {
                $data = array_merge($data, $this->stock->decrementForOrder($order));
            }

            if ($newStatus === OrderStatus::CANCELLED) {
                // Cancelar repone el stock que la orden tenía descontado.
                $data = array_merge($data, $this->stock->restoreForOrder($order));

                // Orden ya paga: el admin puede cancelarla igual, pero queda
                // marcada para gestionar el reembolso a mano (no hay refund
                // automático contra MP).
                if (OrderStatus::paymentOf($order) === OrderStatus::PAYMENT_APPROVED) {
                    $data['refund_pending'] = true;
                }
            }

            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Estado del pedido actualizado correctamente.';
            if ($request->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (DomainError $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getUserMessage()], 422);
            }

            return back()->with('error', $e->getUserMessage());
        }
    }
}
