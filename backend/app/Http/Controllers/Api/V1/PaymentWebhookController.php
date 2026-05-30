<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * POST /api/v1/payments/webhook
     *
     * Webhook para actualizar estado de pago de órdenes (Mercado Pago).
     */
    #[OA\Post(
        path: '/api/v1/payments/webhook',
        operationId: 'paymentWebhook',
        tags: ['Payments'],
        security: [],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', description: 'Tipo de notificación (payment)'),
                    new OA\Property(property: 'data', type: 'object', description: 'Datos de la notificación',
                        properties: [
                            new OA\Property(property: 'id', type: 'string', description: 'ID del pago'),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook procesado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Firma inválida o datos incorrectos'
            ),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $signature = $request->header('x-signature-sha256');
        $payload = $request->getContent();

        if (! $this->verifySignature($signature, $payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Firma inválida',
            ], 400);
        }

        $data = $request->all();
        $type = $data['type'] ?? '';
        $paymentId = $data['data']['id'] ?? '';

        if ($type !== 'payment' || $paymentId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de notificación no soportado',
            ], 400);
        }

        $ordersResult = $this->firestore->listDocuments('orders', 100);
        $orders = $ordersResult['documents'] ?? [];

        foreach ($orders as $order) {
            $externalReference = $order['external_reference'] ?? '';
            if ($externalReference === $paymentId) {
                $paymentStatus = $this->getPaymentStatus($paymentId);
                $this->firestore->updateDocument('orders', $order['id'], [
                    'payment_status' => $paymentStatus,
                    'updated_at' => now()->toISOString(),
                ]);

                return ApiResponse::success(
                    message: 'Pago actualizado: '.$paymentStatus
                );
            }
        }

        return ApiResponse::success(
            message: 'Orden no encontrada para el pago'
        );
    }

    protected function verifySignature(?string $signature, string $payload): bool
    {
        $secret = config('services.mercadopago.webhook_secret');
        if (! $signature || ! $secret) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    protected function getPaymentStatus(string $paymentId): string
    {
        $accessToken = config('services.mercadopago.access_token');
        if (! $accessToken) {
            return 'unknown';
        }

        $url = "https://api.mercadopago.com/v1/payments/{$paymentId}";
        $response = Http::withToken($accessToken)->get($url);

        if ($response->failed()) {
            return 'error';
        }

        $paymentData = $response->json();
        $status = $paymentData['status'] ?? 'unknown';

        $statusMap = [
            'approved' => 'approved',
            'authorized' => 'approved',
            'pending' => 'pending',
            'in_process' => 'pending',
            'rejected' => 'rejected',
            'cancelled' => 'rejected',
            'refunded' => 'refunded',
        ];

        return $statusMap[$status] ?? 'unknown';
    }
}
