<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Order;
use App\Services\FirestoreService;
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

    public function __construct(FirestoreService $firestore)
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
        return [
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'in_process' => 'En proceso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
        ];
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

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $statusFilter = request()->get('status');
        $paymentFilter = request()->get('payment_status');

        $result = $this->firestore->listDocuments($this->getCollectionName(), 10, $startAfter, 'created_at');
        $items = collect($result['documents']);

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['clientId'] ?? $item['client_id'] ?? '', $search) !== false
                    || stripos($item['client_name'] ?? '', $search) !== false
                    || stripos($item['id'] ?? '', $search) !== false;
            });
        }

        if ($statusFilter) {
            $items = $items->where('status', $statusFilter);
        }

        if ($paymentFilter) {
            $items = $items->filter(function ($item) use ($paymentFilter) {
                return ($item['paymentStatus'] ?? $item['payment_status'] ?? '') === $paymentFilter;
            });
        }

        // Datos para el modal "new" (selects de cliente/producto, sin paginar — máx. 100).
        $clientsResult = $this->firestore->listDocuments('clients', 100, null, 'name');
        $clients = collect($clientsResult['documents'] ?? [])->where('active', true)->values();

        $productsResult = $this->firestore->listDocuments('products', 100);
        $products = collect($productsResult['documents'] ?? [])->where('active', true)->values();

        return view("{$this->getViewFolder()}.index", [
            'orders' => $items,
            'clients' => $clients,
            'products' => $products,
            'statuses' => self::statuses(),
            'search' => $search,
            'statusFilter' => $statusFilter,
            'paymentFilter' => $paymentFilter,
            'hasMore' => $result['hasMore'] ?? false,
            'lastDocumentId' => $result['lastDocumentId'] ?? null,
            'page' => $page,
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
            $data = [
                'status' => $request->input('status'),
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
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
