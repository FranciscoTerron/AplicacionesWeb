<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CatalogApiController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/catalog/products
     *
     * Lista productos publicos activos desde Firestore.
     */
    #[OA\Get(
        path: '/api/v1/catalog/products',
        operationId: 'listProducts',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Buscar por nombre, SKU o descripción',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'category',
                in: 'query',
                description: 'ID de categoría para filtrar',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de productos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    public function products(Request $request): JsonResponse
    {
        $search = $request->get('search');
        $category = $request->get('category');

        $result = $this->firestore->listDocuments('products', 200);
        $products = collect($result['documents'] ?? [])
            ->where('active', true)
            ->map(fn (array $p) => $this->normalizeProduct($p))
            ->values()
            ->all();

        if ($search) {
            $products = array_filter($products, function ($p) use ($search) {
                return stripos($p['name'] ?? '', $search) !== false
                    || stripos($p['description'] ?? '', $search) !== false
                    || stripos($p['sku'] ?? '', $search) !== false;
            });
            $products = array_values($products);
        }

        if ($category) {
            $products = array_filter($products, function ($p) use ($category) {
                return ($p['category_id'] ?? '') === $category;
            });
            $products = array_values($products);
        }

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * GET /api/v1/catalog/products/{id}
     *
     * Devuelve un producto publico activo por ID desde Firestore.
     */
    #[OA\Get(
        path: '/api/v1/catalog/products/{id}',
        operationId: 'getProduct',
        tags: ['Catalog'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del producto',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Producto no disponible o no encontrado'
            )
        ]
    )]
    public function product(string $id): JsonResponse
    {
        $product = $this->firestore->getDocument('products', $id);

        if (! $product || ! ($product['active'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no disponible.',
            ], 404);
        }

        $product = $this->normalizeProduct($product);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * GET /api/v1/catalog/categories
     *
     * Lista categorias publicas activas desde Firestore.
     */
    #[OA\Get(
        path: '/api/v1/catalog/categories',
        operationId: 'listCategories',
        tags: ['Catalog'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de categorías',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object'))
                    ]
                )
            )
        ]
    )]
    public function categories(): JsonResponse
    {
        $result = $this->firestore->listDocuments('categories', 100);
        $categories = collect($result['documents'] ?? [])
            ->where('active', true)
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Normaliza los campos booleanos de un producto proveniente de Firestore,
     * que se almacenan como string "1"/"0" en algunos documentos.
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
