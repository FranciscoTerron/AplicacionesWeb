<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Product;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class ProductController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'products';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.products.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.products';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreProductRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateProductRequest::class;
    }

    protected function getModelClass(): string
    {
        return Product::class;
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
        $subcategoryFilter = request()->get('subcategory');
        $statusFilter = request()->get('status');

        $fetchResult = $this->firestore->fetchForPage(
            $this->getCollectionName(),
            $perPage,
            $startAfter,
        );
        $items = collect($fetchResult['documents'] ?? []);

        // Apply search filter (by name, sku or description)
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'] ?? '', $search) !== false ||
                       stripos($item['description'] ?? '', $search) !== false ||
                       stripos($item['sku'] ?? '', $search) !== false;
            })->values();
        }

        // Apply category filter
        if ($categoryFilter) {
            $items = $items->filter(function ($item) use ($categoryFilter) {
                return ($item['category_id'] ?? '') === $categoryFilter;
            })->values();
        }

        // Apply subcategory filter
        if ($subcategoryFilter) {
            $items = $items->filter(function ($item) use ($subcategoryFilter) {
                return ($item['subcategory_id'] ?? '') === $subcategoryFilter;
            })->values();
        }

        // Apply status filter (active/inactive)
        if ($statusFilter) {
            $items = $items->filter(function ($item) use ($statusFilter) {
                $isActive = $item['active'] ?? true;

                return ($statusFilter === 'active' && $isActive) ||
                       ($statusFilter === 'inactive' && ! $isActive);
            })->values();
        }

        // Bulk-fetch categories and subcategories for filter dropdowns
        $categoriesResult = $this->firestore->listDocuments('categories', 100);
        $categories = collect($categoriesResult['documents'] ?? [])->where('active', true)->map(function ($category) {
            if (! isset($category['id']) && isset($category['_document_path'])) {
                $parts = explode('/', $category['_document_path']);
                $category['id'] = end($parts);
            }

            return $category;
        });

        $subcategoriesResult = $this->firestore->listDocuments('subcategories', 100);
        $subcategories = collect($subcategoriesResult['documents'] ?? [])
            ->where('active', true)
            ->map(function ($subcategory) {
                if (! isset($subcategory['id']) && isset($subcategory['_document_path'])) {
                    $parts = explode('/', $subcategory['_document_path']);
                    $subcategory['id'] = end($parts);
                }

                return $subcategory;
            });

        // Paginate filtered results
        $totalFiltered = $items->count();
        $totalPages = intval(ceil($totalFiltered / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageItems = $items->slice($offset, $perPage)->values();

        return ViewFacade::make("{$this->getViewFolder()}.index", [
            'products' => $pageItems,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'subcategoryFilter' => $subcategoryFilter,
            'statusFilter' => $statusFilter,
            'hasMore' => $page < $totalPages,
            'lastDocumentId' => $fetchResult['lastDocumentId'],
            'page' => $page,
            'perPage' => $perPage,
            'totalFiltered' => $totalFiltered,
            'totalPages' => $totalPages,
        ]);
    }

    public function store(): RedirectResponse|JsonResponse
    {
        $this->authorizeRequest('create');

        $requestClass = $this->getStoreRequestClass();
        $request = app($requestClass);

        $validated = $request->validated();

        // Convert active to boolean (comes as string from select)
        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        // Auditoría
        $now = now()->toISOString();
        $userId = auth()->id();
        $validated['created_at'] = $now;
        $validated['updated_at'] = $now;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        try {
            $this->firestore->createDocument($this->getCollectionName(), $validated);

            $message = 'Producto creado correctamente.';
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

        // Convert active to boolean
        $validated['active'] = filter_var($validated['active'], FILTER_VALIDATE_BOOLEAN);

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        try {
            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            $message = 'Producto actualizado correctamente.';
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

    /**
     * Activate (restore) a soft-deleted product.
     * Solo Admin puede reactivar (alineado con CategoryPolicy::activate).
     */
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

            $message = 'Producto activado correctamente.';
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
        // Products use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function show(string $id): RedirectResponse
    {
        // Products use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function edit(string $id): RedirectResponse
    {
        // Products use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }
}
