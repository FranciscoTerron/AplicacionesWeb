<?php

namespace App\Http\Traits;

use App\Domain\Errors\DomainError;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

trait CrudActionsTrait
{
    // --- Métodos abstractos que cada controller debe implementar ---
    abstract protected function getCollectionName(): string;

    abstract protected function getRedirectRoute(): string;

    abstract protected function getViewFolder(): string;

    abstract protected function getStoreRequestClass(): string;

    abstract protected function getUpdateRequestClass(): string;

    abstract protected function getModelClass(): string; // ← Nuevo: retorna FQCN del Model

    // --- Métodos auxiliares (opcionales) ---
    protected function getDomainErrors(): array
    {
        return [];
    }

    protected function getExtraCreateData(): array
    {
        return [];
    }

    protected function getExtraEditData(string $id): array
    {
        return [];
    }

    // --- Propiedades ---
    protected FirestoreService $firestore;

    // ============================================
    // AUTORIZACIÓN
    // ============================================

    /**
     * Autoriza una ability (viewAny, create, update, delete) usando Policies.
     */
    protected function authorizeRequest(string $ability = 'viewAny', $model = null): void
    {
        $authUser = auth()->user();

        if (! $authUser) {
            abort(403, 'No autenticado.');
        }

        if ($model) {
            if (! Gate::forUser($authUser)->allows($ability, $model)) {
                abort(403, 'No tienes permiso para realizar esta acción.');
            }
        } else {
            $modelClass = $this->getModelClass();
            if (! Gate::forUser($authUser)->allows($ability, $modelClass)) {
                abort(403, 'No tienes permiso para realizar esta acción.');
            }
        }
    }

    /**
     * Obtiene una instancia del modelo desde Firestore.
     * Usado para autorización con Gate (instancia real).
     */
    protected function getModelInstance(string $id)
    {
        $modelClass = $this->getModelClass();
        $data = $this->firestore->getDocument($this->getCollectionName(), $id);
        if (! $data) {
            abort(404, 'Registro no encontrado.');
        }
        $model = new $modelClass;
        $model->setRawAttributes($data, true);
        $model->exists = true;

        return $model;
    }

    // ============================================
    // CRUD — READ
    // ============================================

    public function index()
    {
        $this->authorizeRequest('viewAny');

        $page = request()->get('page', 1);
        $startAfter = request()->get('after');
        $search = request()->get('search');

        $result = $this->firestore->listDocuments($this->getCollectionName(), 20, $startAfter);
        $items = collect($result['documents']);

        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'] ?? '', $search) !== false;
            });
        }

        return View::make("{$this->getViewFolder()}.index", [
            'items' => $items,
            'hasMore' => $result['hasMore'] ?? false,
            'lastDocumentId' => $result['lastDocumentId'] ?? null,
            'page' => $page,
        ]);
    }

    public function show(string $id): ViewContract|RedirectResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('view', $model);

        $item = $this->firestore->getDocument($this->getCollectionName(), $id);
        if (! $item) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
        }

        return View::make("{$this->getViewFolder()}.show", [
            'item' => $item,
            'id' => $id,
        ]);
    }

    // ============================================
    // CRUD — CREATE (modal + AJAX)
    // ============================================

    public function create(): ViewContract|JsonResponse
    {
        $this->authorizeRequest('create');

        $data = $this->getExtraCreateData();
        $view = view("{$this->getViewFolder()}._form_fields", $data)->render();

        if (request()->ajax()) {
            return response()->json(['html' => $view]);
        }

        return view("{$this->getViewFolder()}.create", $data);
    }

    public function store(): RedirectResponse|JsonResponse
    {
        $this->authorizeRequest('create');

        // Resolvemos el FormRequest concreto del controller (StoreOrderRequest, etc.)
        // para que se ejecuten sus rules(); inyectar FormRequest base no las dispara.
        $requestClass = $this->getStoreRequestClass();
        /** @var FormRequest $request */
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

    // ============================================
    // CRUD — UPDATE (auditoría + AJAX)
    // ============================================

    public function edit(string $id): ViewContract|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        $item = $this->firestore->getDocument($this->getCollectionName(), $id);
        $data = $this->getExtraEditData($id);
        $data['item'] = $item;
        $data['id'] = $id;

        $view = view("{$this->getViewFolder()}._form_fields", $data)->render();

        if (request()->ajax()) {
            return response()->json(['html' => $view]);
        }

        return view("{$this->getViewFolder()}.edit", $data);
    }

    public function update(string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('update', $model);

        $requestClass = $this->getUpdateRequestClass();
        /** @var FormRequest $request */
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

    // ============================================
    // CRUD — DELETE (baja lógica)
    // ============================================

    public function destroy(string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('delete', $model);

        try {
            // Baja lógica: marcar como inactivo + auditoría
            $data = [
                'active' => false,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Registro eliminado correctamente.';
            if (request()->ajax()) {
                return response()->json(['success' => $message, 'redirect' => route($this->getRedirectRoute())]);
            }

            return redirect()->route($this->getRedirectRoute())->with('success', $message);
        } catch (DomainError $e) {
            if (request()->ajax()) {
                return response()->json(['error' => $e->getUserMessage()], 422);
            }

            return back()->with('error', $e->getUserMessage());
        }
    }
}
