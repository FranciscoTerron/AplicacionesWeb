<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogApiController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/catalog/products
     *
     * Lista productos publicos activos desde Firestore.
     */
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
