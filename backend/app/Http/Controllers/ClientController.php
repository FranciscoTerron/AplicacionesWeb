<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Client;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ClientController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'clients';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.clients.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.clients';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreClientRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateClientRequest::class;
    }

    protected function getModelClass(): string
    {
        return Client::class;
    }

    public function index()
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $statusFilter = request()->get('status'); // Changed to 'status' to match view

        $result = $this->firestore->listDocuments($this->getCollectionName(), 10, $startAfter);
        $items = collect($result['documents']);

        // Apply search filter (name or email)
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'] ?? '', $search) !== false ||
                       stripos($item['email'] ?? '', $search) !== false;
            });
        }

        // Apply status filter
        if ($statusFilter) {
            $items = $items->filter(function ($item) use ($statusFilter) {
                $isActive = $item['active'] ?? true;

                return ($statusFilter === 'active' && $isActive) ||
                       ($statusFilter === 'inactive' && ! $isActive);
            });
        }

        return View::make("{$this->getViewFolder()}.index", [
            'clients' => $items,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'hasMore' => $result['hasMore'] ?? false,
            'lastDocumentId' => $result['lastDocumentId'] ?? null,
            'page' => $page,
        ]);
    }

    public function store(): RedirectResponse|JsonResponse
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $requestClass = $this->getStoreRequestClass();
        $request = app($requestClass);

        $validated = $request->validated();

        // Validación específica para subcategories (categoría debe existir y estar activa)
        if ($this->getCollectionName() === 'subcategories' && isset($validated['category_id'])) {
            $category = $this->firestore->getDocument('categories', $validated['category_id']);
            if (! $category || ! ($category['active'] ?? false)) {
                $error = 'La categoría seleccionada no existe o está inactiva.';

                return $request->ajax()
                    ? response()->json(['error' => $error], 422)
                    : back()->with('error', $error)->withInput();
            }
        }

        // Auditoría
        $now = now()->toISOString();
        $userId = auth()->id();
        $validated['created_at'] = $now;
        $validated['updated_at'] = $now;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        try {
            $this->firestore->createDocument($this->getCollectionName(), $validated);

            $message = 'Registro creado correctamente.';
            if ($request->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (DomainError $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getUserMessage()], 422);
            }

            return back()->with('error', $e->getUserMessage())->withInput();
        }
    }

    public function update(string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        $requestClass = $this->getUpdateRequestClass();
        $request = app($requestClass);

        $validated = $request->validated();

        // Validación específica para subcategories
        if ($this->getCollectionName() === 'subcategories' && isset($validated['category_id'])) {
            $category = $this->firestore->getDocument('categories', $validated['category_id']);
            if (! $category || ! ($category['active'] ?? false)) {
                $error = 'La categoría seleccionada no existe o está inactiva.';

                return $request->ajax()
                    ? response()->json(['error' => $error], 422)
                    : back()->with('error', $error)->withInput();
            }
        }

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        // Si el campo 'active' no se proporcionó, mantener el valor existente
        if (! $request->has('active')) {
            unset($validated['active']);
        }

        try {
            $existing = $this->firestore->getDocument($this->getCollectionName(), $id);
            if (! $existing) {
                return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
            }

            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            $message = 'Registro actualizado correctamente.';
            if ($request->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (DomainError $e) {
            if ($request->ajax()) {
                return response()->json(['error' => $e->getUserMessage()], 422);
            }

            return back()->with('error', $e->getUserMessage())->withInput();
        }
    }

    public function activate(Request $request, string $id)
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        try {
            $data = [
                'active' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Client activated successfully.';
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

    public function deactivate(Request $request, string $id)
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        try {
            $data = [
                'active' => false,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Client deactivated successfully.';
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

    public function create()
    {
        // Clients use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function show(string $id)
    {
        // Clients use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function edit(string $id)
    {
        // Clients use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }
}
