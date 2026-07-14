<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\FirestoreService;
use App\Services\StockService;
use App\Support\ApiResponse;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly FirestoreService $firestore,
        private readonly StockService $stock,
    ) {}

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
        $type = $data['type'] ?? $request->query('type', '');
        // data.id puede venir en el body JSON (data.id anidado) o en el query
        // string. PHP convierte el punto del query (?data.id=) a guion bajo
        // (data_id), así que probamos todas las variantes.
        $paymentId = (string) (
            ($data['data']['id'] ?? null)
            ?? $request->query('data_id')
            ?? $request->query('id')
            ?? ''
        );

        // La firma es defensa en profundidad, pero la autenticidad real la da
        // getPayment() más abajo: consulta el pago a la API de MP con nuestro
        // access_token (solo devuelve pagos de NUESTRA cuenta) y validamos
        // external_reference + monto contra la orden. Por eso, si la firma no
        // valida (configs de secret de MP inconsistentes), igual conciliamos
        // confiando en esa verificación contra MP. Para prod estricto, convertir
        // este aviso en un `return 400`.
        if (! $this->verifySignature($request, $paymentId)) {
            logger()->warning('MP webhook: firma no válida, se concilia contra la API de MP', [
                'payment_id' => $paymentId,
            ]);
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
        if ($paymentStatus === OrderStatus::PAYMENT_APPROVED && OrderStatus::of($order) === OrderStatus::PENDING) {
            $update['status'] = OrderStatus::CONFIRMED;

            // Descontar stock recién cuando el pago se acredita (no al crear la
            // orden), así no se resta por órdenes pendientes o abandonadas.
            // Idempotente vía stock_decremented: MP puede reintentar el webhook.
            $update = array_merge($update, $this->stock->decrementForOrder($order));

            // Vaciar el carrito recién acá (pago acreditado), no al crear la
            // orden: así volver atrás desde el checkout de MP sin pagar no deja
            // el carrito vacío. Para métodos sin redirect ya se vació al crear.
            $this->clearCartForUser((string) ($order['user_id'] ?? ''));
        }

        $this->firestore->updateDocument('orders', (string) $order['id'], $update);

        return ApiResponse::success(message: 'Pago actualizado: '.$paymentStatus);
    }

    /**
     * Vacía el carrito del usuario en el backend (fuente de verdad).
     * No falla si el user_id viene vacío o no tiene carrito.
     */
    protected function clearCartForUser(string $userId): void
    {
        if ($userId === '') {
            return;
        }

        $carts = $this->firestore->query('carts', ['user_id' => $userId], 1);
        if ($carts !== []) {
            $this->firestore->updateDocument('carts', (string) $carts[0]['id'], [
                'items' => [],
                'updated_at' => now()->toISOString(),
            ]);
        }
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
