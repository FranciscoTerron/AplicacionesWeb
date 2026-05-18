<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Shipment\StoreShipmentRequest;
use App\Http\Requests\Shipment\UpdateShipmentRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Shipment;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * UI: Shipments usa modal-only (igual que clients/users/orders). Los métodos
 * create/edit/show redirigen al index porque la interacción ocurre en el modal del listado.
 */
class ShipmentController extends Controller
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
        return 'shipments';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.shipments.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.shipments';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreShipmentRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateShipmentRequest::class;
    }

    protected function getModelClass(): string
    {
        return Shipment::class;
    }

    protected function getExtraCreateData(): array
    {
        $ordersResult = $this->firestore->listDocuments('orders', 100, null, 'created_at');
        $orders = collect($ordersResult['documents'] ?? []);

        return [
            'orders' => $orders,
            'statuses' => Shipment::statuses(),
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

        $result = $this->firestore->listDocuments($this->getCollectionName(), 10, $startAfter, 'created_at');
        $items = collect($result['documents']);

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['tracking_code'] ?? '', $search) !== false
                    || stripos($item['order_id'] ?? '', $search) !== false
                    || stripos($item['carrier'] ?? '', $search) !== false
                    || stripos($item['address'] ?? '', $search) !== false;
            });
        }

        if ($statusFilter) {
            $items = $items->where('status', $statusFilter);
        }

        // Datos para el modal "new" (select de orden asociada, máx. 100).
        $ordersResult = $this->firestore->listDocuments('orders', 100, null, 'created_at');
        $orders = collect($ordersResult['documents'] ?? []);

        return view("{$this->getViewFolder()}.index", [
            'shipments' => $items,
            'orders' => $orders,
            'statuses' => Shipment::statuses(),
            'search' => $search,
            'statusFilter' => $statusFilter,
            'hasMore' => $result['hasMore'] ?? false,
            'lastDocumentId' => $result['lastDocumentId'] ?? null,
            'page' => $page,
        ]);
    }

    public function activate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        if (auth()->user()?->role !== 'admin') {
            abort(403, 'Solo los administradores pueden activar envíos.');
        }

        try {
            $data = [
                'active' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Envío reactivado correctamente.';
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
