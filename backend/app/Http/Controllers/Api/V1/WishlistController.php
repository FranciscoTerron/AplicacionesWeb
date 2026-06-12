<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Wishlist\StoreWishlistRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WishlistController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * GET /api/v1/wishlist
     *
     * Obtiene la lista de deseos del usuario autenticado.
     */
    #[OA\Get(
        path: '/api/v1/wishlist',
        operationId: 'getWishlist',
        tags: ['Wishlist'],
        security: [new OA\Security(name: 'BearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de deseos obtenida',
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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $wishlistResult = $this->firestore->query(
            collection: 'wishlists',
            fields: ['user_id' => $user->id],
            limit: 1
        );

        if (count($wishlistResult) > 0) {
            $wishlist = $wishlistResult[0];
        } else {
            $wishlist = [
                'user_id' => $user->id,
                'items' => [],
            ];
        }

        return ApiResponse::success(
            data: $wishlist,
            message: 'Wishlist obtenida'
        );
    }

    /**
     * POST /api/v1/wishlist
     *
     * Agrega un producto a la lista de deseos del usuario.
     */
    #[OA\Post(
        path: '/api/v1/wishlist',
        operationId: 'storeWishlist',
        tags: ['Wishlist'],
        security: [new OA\Security(name: 'BearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'product_id', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto agregado a wishlist',
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
    public function store(StoreWishlistRequest $request): JsonResponse
    {
        $user = $request->user();
        $productId = $request->validated()['product_id'];

        $wishlistResult = $this->firestore->query(
            collection: 'wishlists',
            fields: ['user_id' => $user->id],
            limit: 1
        );

        if (count($wishlistResult) > 0) {
            $wishlist = $wishlistResult[0];
            $items = $wishlist['items'] ?? [];
        } else {
            $wishlist = null;
            $items = [];
        }

        $exists = in_array($productId, $items);
        if (! $exists) {
            $items[] = $productId;
        }

        if ($wishlist) {
            $updatedWishlist = $this->firestore->updateDocument('wishlists', $wishlist['id'], [
                'items' => $items,
                'updated_at' => now()->toISOString(),
            ]);
        } else {
            $updatedWishlist = $this->firestore->createDocument('wishlists', [
                'user_id' => $user->id,
                'items' => $items,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);
        }

        return ApiResponse::success(
            data: $updatedWishlist,
            message: $exists ? 'Producto ya está en la wishlist' : 'Producto agregado a wishlist'
        );
    }

    /**
     * DELETE /api/v1/wishlist/{id}
     *
     * Elimina un producto de la lista de deseos del usuario.
     */
    #[OA\Delete(
        path: '/api/v1/wishlist/{id}',
        operationId: 'destroyWishlistItem',
        tags: ['Wishlist'],
        security: [new OA\Security(name: 'BearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID del producto a eliminar',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Producto eliminado de wishlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado'
            ),
        ]
    )]
    public function destroy(string $productId, Request $request): JsonResponse
    {
        $user = $request->user();

        $wishlistResult = $this->firestore->query(
            collection: 'wishlists',
            fields: ['user_id' => $user->id],
            limit: 1
        );

        if (count($wishlistResult) === 0) {
            return ApiResponse::success(
                message: 'Wishlist vacía'
            );
        }

        $wishlist = $wishlistResult[0];
        $items = $wishlist['items'] ?? [];
        $items = array_filter($items, function ($item) use ($productId) {
            return $item !== $productId;
        });
        $items = array_values($items);

        $this->firestore->updateDocument('wishlists', $wishlist['id'], [
            'items' => $items,
            'updated_at' => now()->toISOString(),
        ]);

        return ApiResponse::success(
            message: 'Producto eliminado de wishlist'
        );
    }
}
