<?php

namespace App\Http\Controllers;

use App\Domain\Errors\DomainError;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Traits\CrudActionsTrait;
use App\Models\Category;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use CrudActionsTrait;

    public function __construct(FirestoreService $firestore)
    {
        $this->firestore = $firestore;
    }

    protected function getCollectionName(): string
    {
        return 'categories';
    }

    protected function getRedirectRoute(): string
    {
        return 'admin.categories.index';
    }

    protected function getViewFolder(): string
    {
        return 'admin.categories';
    }

    protected function getStoreRequestClass(): string
    {
        return StoreCategoryRequest::class;
    }

    protected function getUpdateRequestClass(): string
    {
        return UpdateCategoryRequest::class;
    }

    protected function getModelClass(): string
    {
        return Category::class;
    }

    public function create(): RedirectResponse
    {
        // Categories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function show(string $id): RedirectResponse
    {
        // Categories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
    }

    public function edit(string $id): RedirectResponse
    {
        // Categories use modal-only approach, redirect to index
        return redirect()->route($this->getRedirectRoute());
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

        $fetchResult = $this->firestore->fetchForPage(
            $this->getCollectionName(),
            $perPage,
            $startAfter,
        );
        $items = collect($fetchResult['documents'] ?? []);

        // Apply search filter
        if ($search) {
            $items = $items->filter(function ($item) use ($search) {
                return stripos($item['name'] ?? '', $search) !== false ||
                       stripos($item['slug'] ?? '', $search) !== false;
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

        // Slice to current page
        $totalFiltered = $items->count();
        $totalPages = intval(ceil($totalFiltered / $perPage));
        $offset = ($page - 1) * $perPage;
        $pageItems = $items->slice($offset, $perPage)->values();

        return view("{$this->getViewFolder()}.index", [
            'categories' => $pageItems,
            'search' => $search,
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
        $this->authorizeRequest('viewAny', $this->getModelClass());

        $requestClass = $this->getStoreRequestClass();
        $request = app($requestClass);

        $validated = $request->validated();

        // Auditoría
        $now = now()->toISOString();
        $userId = auth()->id();
        $validated['created_at'] = $now;
        $validated['updated_at'] = $now;
        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        try {
            $this->firestore->createDocument($this->getCollectionName(), $validated);

            $message = 'Categoría creada correctamente.';
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

        // Auditoría
        $validated['updated_at'] = now()->toISOString();
        $validated['updated_by'] = auth()->id();

        try {
            $existing = $this->firestore->getDocument($this->getCollectionName(), $id);
            if (! $existing) {
                return redirect()->route($this->getRedirectRoute())->with('error', 'Categoría no encontrada.');
            }

            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            $message = 'Categoría actualizada correctamente.';
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

            $message = 'Categoría activada correctamente.';
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
