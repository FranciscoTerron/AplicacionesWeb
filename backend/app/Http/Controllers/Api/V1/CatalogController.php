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
     * Lista productos activos de Firestore con filtros opcionales.
     * Respuestas JSON consistentes para consumo por React.
     */
    public function products(ProductIndexRequest $request): JsonResponse
    {
        $productsResult = $this->firestore->listDocuments(
            collection: 'products',
            limit: 200,
        );

        $products = collect($productsResult['documents'] ?? [])
            ->where('active', true)
            ->values();

        // Filtro por nombre / SKU / descripción
        if ($search = $request->validated('search')) {
            $needle = mb_strtolower($search);
            $products = $products->filter(function ($p) use ($needle) {
                return
                    mb_stripos($p['name'] ?? '', $needle) !== false
                    || mb_stripos($p['description'] ?? '', $needle) !== false
                    || mb_stripos($p['sku'] ?? '', $needle) !== false;
            })->values();
        }

        // Filtro por categoría
        if ($categoryId = $request->validated('category')) {
            $products = $products->where('category_id', $categoryId)->values();
        }

        return ApiResponse::success(
            data: $products->all(),
            message: 'Productos encontrados: '.$products->count()
        );
    }
}
