<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\CartOperationRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CartApiController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * POST /api/v1/cart
     *
     * Operaciones de carrito (add, update, remove).
     */
    #[OA\Post(
        path: '/api/v1/cart',
        operationId: 'cartOperations',
        tags: ['Cart'],
        security: [new OA\Security(name: 'BearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'action', type: 'string', enum: ['add', 'update', 'remove']),
                    new OA\Property(property: 'product_id', type: 'string'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Operación de carrito exitosa',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación'
            ),
        ]
    )]
    public function __invoke(CartOperationRequest $request): JsonResponse
    {
        $user = $request->user();
        $action = (string) $request->input('action');
        $productId = (string) $request->input('product_id', '');
        $quantity = (int) $request->input('quantity', 1);

        // HU-B06: para add/update el producto debe existir, estar activo y con
        // stock, y la cantidad se capea al stock disponible. El carrito deja de
        // aceptar datos que después rompen el checkout; la UI (HU-F05) muestra
        // el mismo tope, pero la fuente de verdad es esta.
        // (update con cantidad 0 es una eliminación: no exige producto vigente,
        // si no un producto dado de baja quedaría clavado en el carrito.)
        $stock = PHP_INT_MAX;
        if ($action === 'add' || ($action === 'update' && $quantity !== 0)) {
            $product = $this->firestore->getDocument('products', $productId);

            if ($product === null || ! ($product['active'] ?? false)) {
                return ApiResponse::error(message: 'El producto no está disponible', status: 422);
            }

            $stock = max(0, (int) ($product['stock'] ?? 0));
            if ($stock === 0) {
                return ApiResponse::error(
                    message: 'Sin stock disponible para: '.($product['name'] ?? $productId),
                    status: 422
                );
            }
        }

        // Obtener carrito actual o crear uno nuevo
        $cartResult = $this->firestore->query(
            collection: 'carts',
            fields: ['user_id' => $user->id],
            limit: 1
        );

        if (count($cartResult) > 0) {
            $cart = $cartResult[0];
            $items = $cart['items'] ?? [];
        } else {
            $cart = null;
            $items = [];
        }

        switch ($action) {
            case 'add':
                // Agregar producto al carrito (capeado al stock disponible)
                $exists = false;
                foreach ($items as &$item) {
                    if (($item['product_id'] ?? '') === $productId) {
                        $item['quantity'] = min(($item['quantity'] ?? 0) + $quantity, $stock);
                        $exists = true;
                        break;
                    }
                }
                unset($item);

                if (! $exists) {
                    $items[] = [
                        'product_id' => $productId,
                        'quantity' => min($quantity, $stock),
                    ];
                }
                break;

            case 'update':
                // Cantidad 0 = quitar el ítem (refinamiento HU-B06); si no,
                // actualizar capeando al stock.
                if ($quantity === 0) {
                    $items = array_values(array_filter($items, function ($item) use ($productId) {
                        return ($item['product_id'] ?? '') !== $productId;
                    }));
                    break;
                }

                foreach ($items as &$item) {
                    if (($item['product_id'] ?? '') === $productId) {
                        $item['quantity'] = min($quantity, $stock);
                        break;
                    }
                }
                unset($item);
                break;

            case 'remove':
                // Eliminar producto
                $items = array_filter($items, function ($item) use ($productId) {
                    return ($item['product_id'] ?? '') !== $productId;
                });
                $items = array_values($items);
                break;

            case 'clear':
                // Vaciar carrito completo en una sola escritura (evita el race
                // de mandar N "remove" en paralelo tras crear la orden).
                $items = [];
                break;
        }

        // Guardar carrito en Firestore
        if ($cart) {
            $updatedCart = $this->firestore->updateDocument('carts', $cart['id'], [
                'items' => $items,
                'updated_at' => now()->toISOString(),
            ]);
        } else {
            $updatedCart = $this->firestore->createDocument('carts', [
                'user_id' => $user->id,
                'items' => $items,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);
        }

        return ApiResponse::success(
            data: $updatedCart,
            message: 'Carrito actualizado'
        );
    }

    /**
     * GET /api/v1/cart
     *
     * Obtiene el carrito del usuario autenticado.
     */
    #[OA\Get(
        path: '/api/v1/cart',
        operationId: 'getCart',
        tags: ['Cart'],
        security: [new OA\Security(name: 'BearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carrito encontrado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $cartResult = $this->firestore->query(
            collection: 'carts',
            fields: ['user_id' => $user->id],
            limit: 1
        );

        if (count($cartResult) > 0) {
            $cart = $cartResult[0];
        } else {
            $cart = [
                'user_id' => $user->id,
                'items' => [],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ];
        }

        return ApiResponse::success(
            data: $cart,
            message: 'Carrito obtenido'
        );
    }
}
