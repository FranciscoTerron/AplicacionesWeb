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
        $data = $request->all();
        $type = $data['type'] ?? '';
        $paymentId = (string) ($data['data']['id'] ?? '');

        if (! $this->verifySignature($request, $paymentId)) {
            return response()->json([
                'success' => false,
                'message' => 'Firma inválida',
            ], 400);
        }

        if ($type !== 'payment' || $paymentId === '') {
            return response()->json([
                'success' => false,
                'message' => 'Tipo de notificación no soportado',
            ], 400);
        }

        // Consultar el pago real en MercadoPago (fuente de verdad del monto
        // y la referencia externa, que NO debe inferirse del webhook).
        $payment = $this->getPayment($paymentId);

        if ($payment === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo verificar el pago con MercadoPago',
            ], 400);
        }

        $externalReference = (string) ($payment['external_reference'] ?? '');
        $orders = $this->firestore->query('orders', ['external_reference' => $externalReference], 1);

        if ($externalReference === '' || $orders === []) {
            // Nada que conciliar; ACK para que MP no reintente.
            return ApiResponse::success(message: 'Orden no encontrada para el pago');
        }

        $order = $orders[0];

        // Validar que el monto pagado coincida con el total de la orden.
        $paidAmount = $payment['transaction_amount'] ?? null;
        if ($paidAmount !== null && (float) ($order['total_amount'] ?? 0) !== (float) $paidAmount) {
            return response()->json([
                'success' => false,
                'message' => 'El monto del pago no coincide con la orden',
            ], 400);
        }

        $paymentStatus = $this->mapStatus((string) ($payment['status'] ?? 'unknown'));

        $update = [
            'payment_status' => $paymentStatus,
            'updated_at' => now()->toISOString(),
        ];

        // Pago aprobado sobre una orden recién creada => confirmarla, así el
        // comprobante no queda "Pendiente" con el pago ya acreditado. No se
        // pisan estados de fulfillment posteriores (shipped, delivered, etc.).
        if ($paymentStatus === 'approved' && (string) ($order['status'] ?? 'pending') === 'pending') {
            $update['status'] = 'confirmed';

            // Descontar stock recién cuando el pago se acredita (no al crear la
            // orden), así no se resta por órdenes pendientes o abandonadas.
            // Idempotente: la bandera evita doble descuento si MP reintenta.
            if (! ($order['stock_decremented'] ?? false)) {
                $oversold = $this->decrementStock($order['items'] ?? []);
                $update['stock_decremented'] = true;
                // Si al acreditarse el pago el stock ya no alcanzaba (otra compra
                // se adelantó), se marca la orden para revisión manual del admin.
                if ($oversold) {
                    $update['oversold'] = true;
                }
            }
        }

        $this->firestore->updateDocument('orders', (string) $order['id'], $update);

        return ApiResponse::success(message: 'Pago actualizado: '.$paymentStatus);
    }

    /**
     * Resta del stock de cada producto la cantidad comprada en la orden.
     * El stock nunca baja de 0. Producto inexistente se ignora.
     * Devuelve true si algún producto no tenía stock suficiente (oversell).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function decrementStock(array $items): bool
    {
        $oversold = false;

        foreach ($items as $item) {
            $productId = (string) ($item['product_id'] ?? '');
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId === '' || $quantity <= 0) {
                continue;
            }

            $product = $this->firestore->getDocument('products', $productId);
            if ($product === null) {
                continue;
            }

            $current = (int) ($product['stock'] ?? 0);
            if ($current < $quantity) {
                $oversold = true;
            }

            $this->firestore->updateDocument('products', $productId, [
                'stock' => max(0, $current - $quantity),
                'updated_at' => now()->toISOString(),
            ]);
        }

        return $oversold;
    }

    /**
     * Verifica la firma del webhook según el esquema de MercadoPago:
     * header `x-signature` = "ts=<ts>,v1=<hmac>", donde el HMAC se calcula
     * sobre el manifest `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`.
     */
    protected function verifySignature(Request $request, string $dataId): bool
    {
        $secret = config('services.mercadopago.webhook_secret');
        $xSignature = $request->header('x-signature');

        if (! $secret || ! $xSignature) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            $pair = array_pad(explode('=', trim($part), 2), 2, '');
            $parts[trim($pair[0])] = trim($pair[1]);
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';

        if ($ts === '' || $v1 === '') {
            return false;
        }

        // MP normaliza data.id a minúsculas si es alfanumérico.
        $normalizedId = strtolower($dataId);
        $requestId = $request->header('x-request-id');

        $manifest = '';
        if ($normalizedId !== '') {
            $manifest .= 'id:'.$normalizedId.';';
        }
        if ($requestId) {
            $manifest .= 'request-id:'.$requestId.';';
        }
        $manifest .= 'ts:'.$ts.';';

        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $v1);
    }

    /**
     * Obtiene el pago desde la API de MercadoPago.
     *
     * @return array<string, mixed>|null
     */
    protected function getPayment(string $paymentId): ?array
    {
        $accessToken = config('services.mercadopago.access_token');
        if (! $accessToken) {
            return null;
        }

        $response = Http::withToken($accessToken)
            ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    protected function mapStatus(string $status): string
    {
        return [
            'approved' => 'approved',
            'authorized' => 'approved',
            'pending' => 'pending',
            'in_process' => 'pending',
            'rejected' => 'rejected',
            'cancelled' => 'rejected',
            'refunded' => 'refunded',
        ][$status] ?? 'unknown';
    }
}
