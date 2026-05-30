<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Catalog\SearchRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SearchController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * POST /api/v1/catalog/search
     *
     * Búsqueda avanzada de productos con filtros.
     */
    #[OA\Post(
        path: '/api/v1/catalog/search',
        operationId: 'searchProducts',
        tags: ['Catalog'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'query', type: 'string', description: 'Término de búsqueda'),
                    new OA\Property(property: 'category_id', type: 'string', description: 'Filtrar por categoría'),
                    new OA\Property(property: 'min_price', type: 'number', description: 'Precio mínimo'),
                    new OA\Property(property: 'max_price', type: 'number', description: 'Precio máximo'),
                    new OA\Property(property: 'min_rating', type: 'number', description: 'Rating mínimo'),
                    new OA\Property(property: 'in_stock', type: 'boolean', description: 'Solo productos en stock'),
                    new OA\Property(property: 'attributes', type: 'object', description: 'Filtros por atributos'),
                    new OA\Property(property: 'sort_by', type: 'string', enum: ['price', 'name', 'rating', 'created_at']),
                    new OA\Property(property: 'sort_order', type: 'string', enum: ['asc', 'desc']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resultados de búsqueda',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->firestore->listDocuments('products', 200);
        $products = collect($result['documents'] ?? [])
            ->where('active', true)
            ->values();

        // Búsqueda full-text
        if ($query = $validated['query'] ?? null) {
            $needle = mb_strtolower($query);
            $products = $products->filter(function ($p) use ($needle) {
                return mb_stripos($p['name'] ?? '', $needle) !== false
                    || mb_stripos($p['description'] ?? '', $needle) !== false
                    || mb_stripos($p['sku'] ?? '', $needle) !== false;
            })->values();
        }

        // Filtro por categoría
        if ($categoryId = $validated['category_id'] ?? null) {
            $products = $products->where('category_id', $categoryId)->values();
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

        // Filtro por rating
        if (isset($validated['min_rating'])) {
            $products = $products->filter(function ($p) use ($validated) {
                return ($p['rating'] ?? 0) >= $validated['min_rating'];
            })->values();
        }

        // Filtro por disponibilidad
        if (isset($validated['in_stock']) && $validated['in_stock']) {
            $products = $products->filter(function ($p) {
                return ($p['stock'] ?? 0) > 0;
            })->values();
        }

        // Ordenamiento
        $sortBy = $validated['sort_by'] ?? null;
        $sortOrder = $validated['sort_order'] ?? 'asc';
        if ($sortBy) {
            $products = $products->sortBy($sortBy, SORT_REGULAR, $sortOrder === 'desc')
                ->values();
        }

        return ApiResponse::success(
            data: $products->all(),
            message: 'Productos encontrados: '.$products->count()
        );
    }
}
