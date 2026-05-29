<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Catalog\ProductIndexRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/catalog/products
     *
     * Lista productos activos de Firestore con filtros opcionales y paginación.
     * Respuestas JSON consistentes para consumo por React.
     */
    public function products(ProductIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Paginación
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 20);
        $offset = ($page - 1) * $limit;

        // Fetch documents con paginación
        $productsResult = $this->firestore->listDocuments(
            collection: 'products',
            limit: $limit + 1, // +1 para detectar si hay más páginas
        );

        $products = collect($productsResult['documents'] ?? [])
            ->where('active', true)
            ->values();

        // Filtro por nombre / SKU / descripción
        if ($search = $validated['search'] ?? null) {
            $needle = mb_strtolower($search);
            $products = $products->filter(function ($p) use ($needle) {
                return
                    mb_stripos($p['name'] ?? '', $needle) !== false
                    || mb_stripos($p['description'] ?? '', $needle) !== false
                    || mb_stripos($p['sku'] ?? '', $needle) !== false;
            })->values();
        }

        // Filtro por categoría
        if ($categoryId = $validated['category'] ?? null) {
            $products = $products->where('category_id', $categoryId)->values();
        }

        // Filtro por featured
        if (isset($validated['featured'])) {
            $products = $products->where('featured', $validated['featured'])->values();
        }

        // Filtro por rango de precios
        if (isset($validated['min_price'])) {
            $products = $products->filter(function ($p) use ($validated) {
                return ($p['price'] ?? 0) >= $validated['min_price'];
            })->values();
        }

        if (isset($validated['max_price'])) {
            $products = $products->filter(function ($p) use ($validated) {
                return ($p['price'] ?? 0) <= $validated['max_price'];
            })->values();
        }

        $total = $products->count();

        // Aplicar paginación (offset/limit) sobre el array filtrado
        $products = $products->slice($offset, $limit)->values();

        $lastPage = (int) ceil($total / $limit);

        return ApiResponse::success(
            data: $products->all(),
            message: 'Productos encontrados: '.$total,
            meta: [
                'total' => $total,
                'page' => $page,
                'last_page' => $lastPage,
                'per_page' => $limit,
            ]
        );
    }
}
