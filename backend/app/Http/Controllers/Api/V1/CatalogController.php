<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Catalog\ProductIndexRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class CatalogController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/catalog/products
     *
     * Lista productos activos de Firestore con filtros opcionales y paginación.
     * Respuestas JSON consistentes para consumo por React.
     */
    #[OA\Get(
        path: '/api/v1/catalog/products',
        operationId: 'listProducts',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Busca por nombre, SKU o descripción',
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'ID de categoría para filtrar',
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'featured',
                in: 'query',
                description: 'Filtra productos destacados',
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'min_price',
                in: 'query',
                description: 'Precio mínimo',
                schema: new OA\Schema(type: 'number', minimum: 0)
            ),
            new OA\Parameter(
                name: 'max_price',
                in: 'query',
                description: 'Precio máximo',
                schema: new OA\Schema(type: 'number', minimum: 0)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Número de página',
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Cantidad por página',
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object', properties: [
                            new OA\Property(property: 'total', type: 'integer'),
                            new OA\Property(property: 'page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'per_page', type: 'integer'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
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
            limit: $limit + 1,
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

    /**
     * GET /api/v1/catalog/featured
     *
     * Lista productos destacados activos desde Firestore.
     */
    #[OA\Get(
        path: '/api/v1/catalog/featured',
        operationId: 'listFeaturedProducts',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos destacados',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
        ]
    )]
    public function featured(): JsonResponse
    {
        $result = $this->firestore->listDocuments('products', 100);
        $products = collect($result['documents'] ?? [])
            ->where('active', true)
            ->where('featured', true)
            ->map(fn (array $p) => $this->normalizeProduct($p))
            ->values()
            ->all();

        return ApiResponse::success(
            data: $products,
            message: 'Productos destacados encontrados: '.count($products)
        );
    }

    /**
     * Normaliza los campos booleanos de un producto proveniente de Firestore.
     */
    protected function normalizeProduct(array $product): array
    {
        foreach (['active', 'featured'] as $field) {
            if (isset($product[$field]) && ! is_bool($product[$field])) {
                $product[$field] = filter_var($product[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $product;
    }
}
