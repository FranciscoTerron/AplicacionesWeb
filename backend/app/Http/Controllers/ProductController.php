<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Product;
use App\Services\CloudinaryService;
use App\Services\DiscountService;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;

class ProductController extends Controller
{
    use CrudActionsTrait;

    protected CloudinaryService $cloudinary;

    protected DiscountService $discounts;

    public function __construct(FirestoreService $firestore, CloudinaryService $cloudinary, DiscountService $discounts)
    {
        $this->firestore = $firestore;
        $this->cloudinary = $cloudinary;
        $this->discounts = $discounts;
    }

    /**
     * Extrae los public_id que estaban en $existing pero ya no están en $validated.
     *
     * @param  array<string,mixed>  $existing
     * @param  array<string,mixed>  $validated
     * @return array<int,string>
     */
    /**
     * Filtra items inválidos (nulls, sin url, sin public_id) y reindexa.
     *
     * @param  mixed  $images
     * @return array<int,array{url:string,public_id:string}>
     */
    protected function sanitizeImages($images): array
    {
        if (! is_array($images)) {
            return [];
        }

        $clean = [];
        foreach ($images as $img) {
            if (is_array($img) && ! empty($img['url']) && ! empty($img['public_id'])) {
                $clean[] = ['url' => (string) $img['url'], 'public_id' => (string) $img['public_id']];
            }
        }

        return $clean;
    }

    protected function diffRemovedImagePublicIds(array $existing, array $validated): array
    {
        $extractIds = function (array $doc): array {
            $ids = [];
            foreach (($doc['images'] ?? []) as $img) {
                if (is_array($img) && ! empty($img['public_id']) && is_string($img['public_id'])) {
                    $ids[] = $img['public_id'];
                }
            }

            return $ids;
        };

        return array_values(array_diff($extractIds($existing), $extractIds($validated)));
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
        $sort = request()->get('sort', 'name');
        $order = request()->get('order', 'asc');

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

        // Apply sorting
        $sortableFields = ['name', 'price', 'stock', 'created_at'];
        if (in_array($sort, $sortableFields)) {
            $items = $items->sortBy($sort, SORT_REGULAR, $order === 'desc')->values();
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
        // Decorar solo la página visible: agrega final_price/discount para mostrar.
        $pageItems = $this->discounts->decorateMany($items->slice($offset, $perPage)->values());

        return ViewFacade::make("{$this->getViewFolder()}.index", [
            'products' => $pageItems,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'subcategoryFilter' => $subcategoryFilter,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'order' => $order,
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

        // Sanitize images: filtrar nulls e items sin url/public_id.
        $validated['images'] = $this->sanitizeImages($validated['images'] ?? []);

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

        // Sanitize images
        $validated['images'] = $this->sanitizeImages($validated['images'] ?? []);

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        try {
            // Reusamos el doc ya cargado por getModelInstance() para no hacer otra lectura.
            $existing = $model->getAttributes();
            $removedPublicIds = $this->diffRemovedImagePublicIds($existing, $validated);

            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            if ($removedPublicIds !== []) {
                $this->cloudinary->deleteAssets($removedPublicIds);
            }

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
