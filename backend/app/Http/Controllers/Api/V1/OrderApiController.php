<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\StoreOrderRequest;
use App\Services\FirestoreService;
use App\Services\MercadoPagoService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Throwable;

class OrderApiController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly MercadoPagoService $mercadoPago,
    ) {}

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

        // Resolver precios server-side: el cliente NO define el precio.
        $items = [];
        $totalAmount = 0;
        foreach ($validated['items'] as $item) {
            $product = $this->firestore->getDocument('products', $item['product_id']);

            if (! $product || ! ($product['active'] ?? false)) {
                return ApiResponse::error(
                    message: 'Producto no disponible: '.$item['product_id'],
                    status: 422
                );
            }

            $quantity = (int) $item['quantity'];
            $stock = (int) ($product['stock'] ?? 0);

            if ($stock < $quantity) {
                return ApiResponse::error(
                    message: 'Stock insuficiente para: '.$item['product_id'],
                    status: 422
                );
            }

            $price = (float) ($product['price'] ?? 0);
            $items[] = [
                'product_id' => $item['product_id'],
                'name' => $product['name'] ?? null,
                'quantity' => $quantity,
                'price' => $price,
            ];
            $totalAmount += $price * $quantity;
        }

        $orderData = [
            'user_id' => $user->id,
            'items' => $items,
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

        // Filtrar por user_id server-side (evita perder órdenes fuera del
        // primer lote y no depende de un orderBy global).
        $orders = $this->firestore->query('orders', ['user_id' => $user->id], 200);

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

    /**
     * GET /api/v1/orders/{id}
     *
     * Obtener detalle de una orden específica del cliente autenticado.
     */
    #[OA\Get(
        path: '/api/v1/orders/{id}',
        operationId: 'getOrder',
        tags: ['Orders'],
        security: [new OA\Security(name: 'BearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la orden',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Orden encontrada',
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
                response: 404,
                description: 'Orden no encontrada'
            ),
        ]
    )]
    public function show(string $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $order = $this->firestore->getDocument('orders', $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        // Verificar que la orden pertenece al usuario autenticado
        if (($order['user_id'] ?? null) !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para ver esta orden.',
            ], 404);
        }

        return ApiResponse::success(
            data: $order,
            message: 'Orden encontrada'
        );
    }

    /**
     * PUT /api/v1/orders/{id}/cancel
     *
     * Cancelar una orden (solo si está en estado cancelable).
     */
    #[OA\Put(
        path: '/api/v1/orders/{id}/cancel',
        operationId: 'cancelOrder',
        tags: ['Orders'],
        security: [new OA\Security(name: 'BearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la orden',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Orden cancelada exitosamente',
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
                response: 400,
                description: 'No se puede cancelar la orden en su estado actual'
            ),
            new OA\Response(
                response: 404,
                description: 'Orden no encontrada'
            ),
        ]
    )]
    public function cancel(string $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $order = $this->firestore->getDocument('orders', $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        // Verificar que la orden pertenece al usuario autenticado
        if (($order['user_id'] ?? null) !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para cancelar esta orden.',
            ], 404);
        }

        // Estados que permiten cancelación
        $currentStatus = $order['status'] ?? 'pending';
        $cancelableStatuses = ['pending', 'confirmed'];

        if (! in_array($currentStatus, $cancelableStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'La orden no se puede cancelar en su estado actual: '.$currentStatus,
            ], 400);
        }

        $updatedOrder = $this->firestore->updateDocument('orders', $id, [
            'status' => 'cancelled',
            'updated_at' => now()->toISOString(),
        ]);

        return ApiResponse::success(
            data: $updatedOrder,
            message: 'Orden cancelada exitosamente'
        );
    }

    /**
     * POST /api/v1/orders/{id}/pay
     *
     * Crea una preference de Mercado Pago (Checkout Pro) para la orden y
     * devuelve el init_point al que el frontend redirige al usuario.
     */
    #[OA\Post(
        path: '/api/v1/orders/{id}/pay',
        operationId: 'payOrder',
        tags: ['Orders'],
        security: [new OA\Security(name: 'BearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'ID de la orden',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preference creada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'init_point', type: 'string'),
                            new OA\Property(property: 'preference_id', type: 'string'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'La orden no pertenece al usuario'),
            new OA\Response(response: 404, description: 'Orden no encontrada'),
            new OA\Response(response: 422, description: 'La orden no admite pago en su estado actual'),
            new OA\Response(response: 502, description: 'No se pudo iniciar el pago con Mercado Pago'),
        ]
    )]
    public function pay(string $id, Request $request): JsonResponse
    {
        $user = $request->user();

        $order = $this->firestore->getDocument('orders', $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada.',
            ], 404);
        }

        if (($order['user_id'] ?? null) !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permiso para pagar esta orden.',
            ], 403);
        }

        // Solo se paga una orden pendiente cuyo pago no está aún aprobado.
        $status = (string) ($order['status'] ?? 'pending');
        $paymentStatus = (string) ($order['payment_status'] ?? 'pending');

        if ($status !== 'pending' || ! in_array($paymentStatus, ['pending', 'rejected'], true)) {
            return ApiResponse::error(
                message: 'La orden no admite pago en su estado actual.',
                status: 422
            );
        }

        try {
            $preference = $this->mercadoPago->createPreference($order);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                message: 'No se pudo iniciar el pago. Intentá nuevamente.',
                status: 502
            );
        }

        // external_reference = id de la orden: así el webhook concilia el pago.
        $this->firestore->updateDocument('orders', $id, [
            'external_reference' => $id,
            'payment_preference_id' => $preference['preference_id'],
            'updated_at' => now()->toISOString(),
        ]);

        return ApiResponse::success(
            data: $preference,
            message: 'Preference creada'
        );
    }
}
