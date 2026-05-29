<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class OrderApiController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * POST /api/v1/orders
     *
     * Crea una nueva orden desde el frontend.
     */
    #[OA\Post(
        path: '/api/v1/orders',
        operationId: 'createOrder',
        tags: ['Orders'],
        security: [new OA\Security(name: 'BearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'shipping_address', type: 'string'),
                    new OA\Property(property: 'payment_method', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Orden creada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Calcular total
        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $totalAmount += ($item['price'] * $item['quantity']);
        }

        $orderData = [
            'user_id' => $user->id,
            'items' => $validated['items'],
            'shipping_address' => $validated['shipping_address'],
            'payment_method' => $validated['payment_method'],
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
        ];

        $order = $this->firestore->createDocument('orders', $orderData);

        return ApiResponse::success(
            data: $order,
            message: 'Orden creada exitosamente',
            status: 201
        );
    }

    /**
     * GET /api/v1/orders
     *
     * Listar órdenes del cliente autenticado con filtros opcionales.
     */
    #[OA\Get(
        path: '/api/v1/orders',
        operationId: 'listOrders',
        tags: ['Orders'],
        security: [new OA\Security(name: 'BearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filtrar por estado',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'date_from',
                in: 'query',
                description: 'Filtrar desde fecha',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                description: 'Filtrar hasta fecha',
                schema: new OA\Schema(type: 'string', format: 'date')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de órdenes',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
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

        // Obtener todas las órdenes del usuario
        $result = $this->firestore->listDocuments('orders', 100);
        $orders = collect($result['documents'] ?? [])
            ->where('user_id', $user->id)
            ->values()
            ->all();

        // Filtro por estado
        if ($status = $request->get('status')) {
            $orders = array_filter($orders, function ($o) use ($status) {
                return ($o['status'] ?? '') === $status;
            });
            $orders = array_values($orders);
        }

        // Filtro por rango de fechas
        if ($dateFrom = $request->get('date_from')) {
            $orders = array_filter($orders, function ($o) use ($dateFrom) {
                $createdAt = $o['created_at'] ?? '';

                return $createdAt >= $dateFrom;
            });
            $orders = array_values($orders);
        }

        if ($dateTo = $request->get('date_to')) {
            $orders = array_filter($orders, function ($o) use ($dateTo) {
                $createdAt = $o['created_at'] ?? '';

                return $createdAt <= $dateTo;
            });
            $orders = array_values($orders);
        }

        return ApiResponse::success(
            data: $orders,
            message: 'Órdenes encontradas: '.count($orders)
        );
    }
}
