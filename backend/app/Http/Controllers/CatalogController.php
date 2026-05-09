<?php

namespace App\Http\Controllers;

use App\Services\FirestoreService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CatalogController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    public function index(): View
    {
        $search = request()->get('search');
        $categoryFilter = request()->get('category');

        $productsResult = $this->firestore->listDocuments('products', 200);
        $products = collect($productsResult['documents'] ?? [])
            ->where('active', true);

        if ($search) {
            $products = $products->filter(function ($p) use ($search) {
                return stripos($p['name'] ?? '', $search) !== false
                    || stripos($p['description'] ?? '', $search) !== false
                    || stripos($p['sku'] ?? '', $search) !== false;
            });
        }

        if ($categoryFilter) {
            $products = $products->where('category_id', $categoryFilter);
        }

        $categoriesResult = $this->firestore->listDocuments('categories', 100);
        $categories = collect($categoriesResult['documents'] ?? [])->where('active', true);

        return view('pages.catalog.index', [
            'products' => $products->values(),
            'categories' => $categories,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
        ]);
    }

    public function show(string $id): View|RedirectResponse
    {
        $product = $this->firestore->getDocument('products', $id);

        if (! $product || ! ($product['active'] ?? false)) {
            return redirect()->route('catalog.index')->with('error', 'Producto no disponible.');
        }

        $category = null;
        if (! empty($product['category_id'])) {
            $category = $this->firestore->getDocument('categories', $product['category_id']);
        }

        return view('pages.catalog.show', [
            'product' => $product,
            'productId' => $id,
            'category' => $category,
        ]);
    }
}
