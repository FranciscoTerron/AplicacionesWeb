<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Subcategory\StoreSubcategoryRequest;
use App\Http\Requests\Subcategory\UpdateSubcategoryRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Subcategory;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubcategoryController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'subcategories';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.subcategories.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.subcategories';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreSubcategoryRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateSubcategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return Subcategory::class;
    }

    public function index(): View
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $page = max(1, intval(request()->get('page', 1)));
        $perPage = intval(request()->get('per_page', 10));
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        $startAfter = request()->get('after');
        $search = request()->get('search');
        $categoryFilter = request()->get('category');
        $statusFilter = request()->get('status');

        $fetchResult = $this->firestore->fetchForPage(
            $this->getCollectionName(),
            $perPage,
            $startAfter,
        );
        $items = collect($fetchResult['documents'] ?? []);

        // Apply search filter (name or slug)
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'] ?? '', $search) !== false ||
                       stripos($item['slug'] ?? '', $search) !== false;
            })->values();
        }

        // Apply category filter
        if ($categoryFilter) {
            $items = $items->filter(function ($item) use ($categoryFilter) {
                return ($item['category_id'] ?? '') === $categoryFilter;
            })->values();
        }

        // Apply status filter
        if ($statusFilter) {
            $items = $items->filter(function ($item) use ($statusFilter) {
                $isActive = $item['active'] ?? true;

                return ($statusFilter === 'active' && $isActive) ||
                       ($statusFilter === 'inactive' && ! $isActive);
            })->values();
        }

        // Bulk-fetch categories for filter dropdown (no per-page for select lists)
        $categoriesResult = $this->firestore->listDocuments('categories', 100);
        $categories = collect($categoriesResult['documents'] ?? [])->where('active', true)->map(function ($category) {
            if (! isset($category['id']) && isset($category['_document_path'])) {
                $parts = explode('/', $category['_document_path']);
                $category['id'] = end($parts);
            }

            return $category;
        });

        // Paginate filtered results
        $totalFiltered = $items->count();
        $totalPages = intval(ceil($totalFiltered / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageItems = $items->slice($offset, $perPage)->values();
        $lastDocumentId = $pageItems->last()['id'] ?? null;

        return view("{$this->getViewFolder()}.index", [
            'subcategories' => $pageItems,
            'categories' => $categories,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter,
            'hasMore' => $page < $totalPages,
            'lastDocumentId' => $lastDocumentId,
            'page' => $page,
            'perPage' => $perPage,
            'totalFiltered' => $totalFiltered,
            'totalPages' => $totalPages,
        ]);
    }

    public function store(): RedirectResponse|JsonResponse
    {
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $requestClass = $this->getStoreRequestClass();
        $request = app($requestClass);

        $validated = $request->validated();

        // Validación específica para subcategories: categoría debe existir y estar activa
        $category = $this->firestore->getDocument('categories', $validated['category_id']);
        if (! $category || ! ($category['active'] ?? false)) {
            return $request->ajax()
                ? response()->json(['error' => 'La categoría seleccionada no existe o está inactiva.'], 422)
                : back()->with('error', 'La categoría seleccionada no existe o está inactiva.')->withInput();
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

            $message = 'Subcategoría creada correctamente.';
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

        // Validación específica para subcategories: categoría debe existir y estar activa
        $category = $this->firestore->getDocument('categories', $validated['category_id']);
        if (! $category || ! ($category['active'] ?? false)) {
            return $request->ajax()
                ? response()->json(['error' => 'La categoría seleccionada no existe o está inactiva.'], 422)
                : back()->with('error', 'La categoría seleccionada no existe o está inactiva.')->withInput();
        }

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        try {
            $existing = $this->firestore->getDocument($this->getCollectionName(), $id);
            if (! $existing) {
                return redirect()->route($this->getRedirectRoute())->with('error', 'Subcategoría no encontrada.');
            }

            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            $message = 'Subcategoría actualizada correctamente.';
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

    public function activate(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $model = $this->getModelInstance($id);
        $this->authorizeRequest('activate', $model);

        try {
            $data = [
                'active' => true,
                'updated_at' => now()->toISOString(),
                'updated_by' => auth()->id(),
            ];
            $this->firestore->updateDocument($this->getCollectionName(), $id, $data);

            $message = 'Subcategoría activada correctamente.';
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

    public function create(): RedirectResponse
    {
        // Subcategories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function show(string $id): RedirectResponse
    {
        // Subcategories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function edit(string $id): RedirectResponse
    {
        // Subcategories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }
}
