<?php

namespace App\Http\Controllers;

use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Discount;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DiscountController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'discounts';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.discounts.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.discounts';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreDiscountRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateDiscountRequest::class;
    }

    protected function getModelClass(): string
    {
        return Discount::class;
    }

    /**
     * Override index to add custom search and filters for discounts
     */
    public function index()
    {
        $this->authorizeRequest('viewAny');

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $typeFilter = request()->get('type');
        $statusFilter = request()->get('status');

        $result = $this->firestore->listDocuments($this->getCollectionName(), 20, $startAfter);
        $items = collect($result['documents']);

        // Apply search filter (by code or name)
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['code'] ?? '', $search) !== false ||
                       stripos($item['name'] ?? '', $search) !== false;
            });
        }

        // Apply type filter (percentage/fixed)
        if ($typeFilter) {
            $items = $items->where('discount_type', $typeFilter);
        }

        // Apply status filter (active/inactive)
        if ($statusFilter !== null) {
            $statusBool = $statusFilter === 'active';
            $items = $items->filter(function ($item) use ($statusBool) {
                return ($item['active'] ?? true) == $statusBool;
            });
        }

        return View::make("{$this->getViewFolder()}.index", [
            'items' => $items,
            'hasMore' => $result['hasMore'] ?? false,
            'lastDocumentId' => $result['lastDocumentId'] ?? null,
            'page' => $page,
            'search' => $search,
            'typeFilter' => $typeFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Create method - redirects to index since all creation is handled via modal
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('admin.discounts.index');
    }

    /**
     * Show method - redirects to index since all viewing is handled via modal
     */
    public function show(string $id): RedirectResponse
    {
        return redirect()->route('admin.discounts.index');
    }

    /**
     * Edit method - redirects to index since all editing is handled via modal
     */
    public function edit(string $id): RedirectResponse
    {
        return redirect()->route('admin.discounts.index');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeRequest('create');

        // Manual validation instead of FormRequest
        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'active' => 'nullable|in:0,1,true,false',
            'applies_to' => 'required|in:all,categories,products',
            'applicable_ids' => 'nullable|string',
        ];

        $validated = $request->validate($rules);

        // Convert applicable_ids from JSON string to array
        if (isset($validated['applicable_ids']) && is_string($validated['applicable_ids'])) {
            $validated['applicable_ids'] = json_decode($validated['applicable_ids'], true) ?? [];
        }

        // Additional validation for applicable_ids
        if ($validated['applies_to'] !== 'all' && empty($validated['applicable_ids'])) {
            return $request->ajax()
                ? response()->json(['errors' => ['applicable_ids' => ['Debes seleccionar al menos un ítem aplicable cuando applies_to no es "all".']]], 422)
                : back()->withErrors(['applicable_ids' => 'Debes seleccionar al menos un ítem aplicable cuando applies_to no es "all".'])->withInput();
        }

        // Check for duplicate code
        $existing = $this->firestore->query('discounts', ['code' => strtoupper($validated['code'])], 1);
        if (count($existing) > 0) {
            $error = 'Ya existe un descuento con ese código.';

            return $request->ajax()
                ? response()->json(['errors' => ['code' => [$error]]], 422)
                : back()->withErrors(['code' => $error])->withInput();
        }

        // Convert active to boolean
        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        // Auditoría
        $now = now()->toISOString();
        $userId = auth()->id();
        $validated['created_at'] = $now;
        $validated['updated_at'] = $now;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        $this->firestore->createDocument($this->getCollectionName(), $validated);

        $message = 'Descuento creado correctamente.';
        if ($request->ajax()) {
            return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
        }

        return redirect()->route($this->getRedirectRoute())->with('success', $message);
    }

    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        // Manual validation instead of FormRequest
        $rules = [
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_to' => 'required|date|after:valid_from',
            'active' => 'nullable|in:0,1,true,false',
            'applies_to' => 'required|in:all,categories,products',
            'applicable_ids' => 'nullable|string', // Accept as string, we'll convert later
        ];

        $validated = $request->validate($rules);

        // Convert applicable_ids from JSON string to array
        if (isset($validated['applicable_ids']) && is_string($validated['applicable_ids'])) {
            $validated['applicable_ids'] = json_decode($validated['applicable_ids'], true) ?? [];
        }

        // Additional validation for applicable_ids
        if ($validated['applies_to'] !== 'all' && empty($validated['applicable_ids'])) {
            return $request->ajax()
                ? response()->json(['errors' => ['applicable_ids' => ['Debes seleccionar al menos un ítem aplicable cuando applies_to no es "all".']]], 422)
                : back()->withErrors(['applicable_ids' => 'Debes seleccionar al menos un ítem aplicable cuando applies_to no es "all".'])->withInput();
        }

        // Check for duplicate code (excluding current discount)
        $existing = $this->firestore->query('discounts', ['code' => strtoupper($validated['code'])], 1);
        if (count($existing) > 0 && ($existing[0]['id'] ?? '') !== $id) {
            $error = 'Ya existe un descuento con ese código.';

            return $request->ajax()
                ? response()->json(['errors' => ['code' => [$error]]], 422)
                : back()->withErrors(['code' => $error])->withInput();
        }

        // Convert active to boolean
        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        // Get existing data and merge
        $existing = $this->firestore->getDocument($this->getCollectionName(), $id);
        if (! $existing) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
        }

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

        $message = 'Descuento actualizado correctamente.';
        if ($request->ajax()) {
            return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
        }

        return redirect()->route($this->getRedirectRoute())->with('success', $message);
    }

    /**
     * Activate (restore) a soft-deleted discount.
     * Only admins can perform this action.
     */
    public function activate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = auth()->user();

        // Only admins can activate discounts
        if (! $authUser || $authUser->role !== 'admin') {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }

        try {
            // Reactivate the discount
            $data = [
                'active' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Descuento reactivado correctamente.';
            if ($request->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['error' => 'Error al reactivar el descuento.'], 500);
            }

            return redirect()->route($this->getRedirectRoute())->with('error', 'Error al reactivar el descuento.');
        }
    }
}
