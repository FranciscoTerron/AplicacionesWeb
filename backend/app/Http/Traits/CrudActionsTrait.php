<?php

namespace App\Http\Traits;

use App\Domain\Errors\DomainError;
use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;

trait CrudActionsTrait
{
    abstract protected function getCollectionName(): string;

    abstract protected function getRedirectRoute(): string;

    abstract protected function getViewFolder(): string;

    abstract protected function getStoreRequestClass(): string;

    abstract protected function getUpdateRequestClass(): string;

    protected FirestoreService $firestore;

    public function index()
    {
        $this->authorizeRequest();

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
            'nextPageToken' => $result['nextPageToken'] ?? null,
        ]);
    }

    public function create(): View
    {
        $this->authorizeRequest();

        return View::make("{$this->getViewFolder()}.create");
    }

    public function store(FormRequest $request): RedirectResponse
    {
        $this->authorizeRequest();

        $validated = $request->validated();

        // Get categoryId for subcategories
        if ($this->getCollectionName() === 'subcategories' && isset($validated['categoryId'])) {
            $category = $this->firestore->getDocument('categories', $validated['categoryId']);
            if (! $category) {
                return back()->with('error', 'La categoría seleccionada no existe.')->withInput();
            }
        }

        try {
            $this->firestore->createDocument($this->getCollectionName(), $validated);

            return redirect()->route($this->getRedirectRoute())->with('success', 'Registro creado correctamente.');
        } catch (DomainError $e) {
            return back()->with('error', $e->getUserMessage())->withInput();
        }
    }

    public function show(string $id): View|RedirectResponse
    {
        $this->authorizeRequest();

        $item = $this->firestore->getDocument($this->getCollectionName(), $id);

        if (! $item) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
        }

        return View::make("{$this->getViewFolder()}.show", [
            'item' => $item,
            'id' => $id,
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $this->authorizeRequest();

        $item = $this->firestore->getDocument($this->getCollectionName(), $id);

        if (! $item) {
            return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
        }

        return View::make("{$this->getViewFolder()}.edit", [
            'item' => $item,
            'id' => $id,
        ]);
    }

    public function update(FormRequest $request, string $id): RedirectResponse
    {
        $this->authorizeRequest();

        $validated = $request->validated();

        // Validate category exists for subcategories
        if ($this->getCollectionName() === 'subcategories' && isset($validated['categoryId'])) {
            $category = $this->firestore->getDocument('categories', $validated['categoryId']);
            if (! $category) {
                return back()->with('error', 'La categoría seleccionada no existe.')->withInput();
            }
        }

        try {
            $existing = $this->firestore->getDocument($this->getCollectionName(), $id);

            if (! $existing) {
                return redirect()->route($this->getRedirectRoute())->with('error', 'Registro no encontrado.');
            }

            $this->firestore->updateDocument($this->getCollectionName(), $id, $validated);

            return redirect()->route($this->getRedirectRoute())->with('success', 'Registro actualizado correctamente.');
        } catch (DomainError $e) {
            return back()->with('error', $e->getUserMessage())->withInput();
        }
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorizeRequest();

        try {
            $this->firestore->deleteDocument($this->getCollectionName(), $id);

            return redirect()->route($this->getRedirectRoute())->with('success', 'Registro eliminado correctamente.');
        } catch (DomainError $e) {
            return back()->with('error', $e->getUserMessage());
        }
    }

    protected function authorizeRequest(): void
    {
        $authUser = auth()->user();

        if (! $authUser || $authUser->role !== 'admin') {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    }
}
