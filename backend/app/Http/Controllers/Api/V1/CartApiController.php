<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $action = $request->get('action', 'add');
        $productId = $request->get('product_id');
        $quantity = (int) $request->get('quantity', 1);

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
                // Agregar producto al carrito
                $exists = false;
                foreach ($items as &$item) {
                    if (($item['product_id'] ?? '') === $productId) {
                        $item['quantity'] = ($item['quantity'] ?? 0) + $quantity;
                        $exists = true;
                        break;
                    }
                }
                unset($item);

                if (! $exists) {
                    $items[] = [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                    ];
                }
                break;

            case 'update':
                // Actualizar cantidad
                foreach ($items as &$item) {
                    if (($item['product_id'] ?? '') === $productId) {
                        $item['quantity'] = $quantity;
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
}
