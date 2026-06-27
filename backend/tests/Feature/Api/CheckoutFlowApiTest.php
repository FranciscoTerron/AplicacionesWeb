<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * Test de integración end-to-end del flujo de compra + pago Mercado Pago,
 * todo en modo prueba (sin tocar la API real: Http::fake).
 *
 * Encadena los tres pasos que en producción hacen el "comprobante pagado":
 *   1. POST /orders            -> crea la orden (status/payment_status = pending)
 *   2. POST /orders/{id}/pay   -> crea la preference y fija external_reference
 *   3. POST /payments/webhook  -> MP notifica "approved" y la orden queda pagada
 *
 * Las firmas y montos siguen el esquema oficial de MP (igual que
 * PaymentWebhookApiTest), así que valida el contrato completo.
 */
class CheckoutFlowApiTest extends TestCase
{
    use InteractsWithApi;

    private const SECRET = 'webhook-secret-test';

    private const REQUEST_ID = 'req-e2e-1';

    private const TS = '1700000000';

    private const PAYMENT_ID = 'pay-e2e-1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();

        config([
            'services.mercadopago.access_token' => 'TEST-token',
            'services.mercadopago.webhook_secret' => self::SECRET,
            'services.mercadopago.frontend_url' => 'https://tienda.test',
            'app.url' => 'https://api.test',
        ]);
    }

    private function validSignature(string $dataId): string
    {
        $manifest = 'id:'.$dataId.';request-id:'.self::REQUEST_ID.';ts:'.self::TS.';';
        $v1 = hash_hmac('sha256', $manifest, self::SECRET);

        return 'ts='.self::TS.',v1='.$v1;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function postWebhook(array $body): TestResponse
    {
        $dataId = (string) ($body['data']['id'] ?? '');

        return $this->call('POST', '/api/v1/payments/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_SIGNATURE' => $this->validSignature($dataId),
            'HTTP_X_REQUEST_ID' => self::REQUEST_ID,
        ], json_encode($body));
    }

    public function test_full_checkout_marks_order_as_paid_in_test_mode(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1', 'name' => 'Ada Lovelace']);

        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro granulado', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);

        // --- 1. Crear la orden ---------------------------------------------
        $create = $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
            'shipping_address' => 'Calle Falsa 123',
            'payment_method' => 'mercado_pago',
        ], $headers);

        $create->assertStatus(201)
            ->assertJson(['data' => [
                'total_amount' => 200,
                'status' => 'pending',
                'payment_status' => 'pending',
                'client_name' => 'Ada Lovelace',
            ]]);

        $orderId = (string) $create->json('data.id');
        $this->assertNotSame('', $orderId);

        // --- 2. Pagar: crea la preference y persiste external_reference -----
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-e2e',
                'init_point' => 'https://mp.test/checkout/pref-e2e',
            ]),
            // El webhook luego consulta el pago real: devolvemos "approved".
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'external_reference' => $orderId,
                'transaction_amount' => 200,
            ]),
        ]);

        $pay = $this->postJson("/api/v1/orders/{$orderId}/pay", [], $headers);
        $pay->assertStatus(200)
            ->assertJson(['data' => ['preference_id' => 'pref-e2e']]);

        $this->assertSame($orderId, $this->firestore->getDocument('orders', $orderId)['external_reference']);

        // --- 3. Webhook de MP: pago aprobado -> comprobante pagado ----------
        $this->postWebhook(['type' => 'payment', 'data' => ['id' => self::PAYMENT_ID]])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $paid = $this->firestore->getDocument('orders', $orderId);
        $this->assertSame('approved', $paid['payment_status']);
        // Pago acreditado => la orden se confirma (ya no figura "Pendiente").
        $this->assertSame('confirmed', $paid['status']);
    }

    public function test_coupon_applies_when_better_than_auto_discount(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);
        // Cupón 20% que NO aplica automático a p1 (scope ajeno), pero sí como
        // cupón de carrito. Sin descuento automático, el cupón gana.
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'VERANO20', 'active' => true,
            'discount_type' => 'percentage', 'value' => 20,
            'applies_to' => 'products', 'applicable_ids' => ['otro-producto'],
        ]]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
            'shipping_address' => 'x',
            'payment_method' => 'cash',
            'discount_code' => 'VERANO20',
        ], $headers)->assertStatus(201)->assertJson(['data' => [
            'subtotal' => 200,
            'total_amount' => 160,
            'coupon' => ['code' => 'VERANO20', 'amount' => 40],
        ]]);
    }

    public function test_coupon_ignored_when_auto_discount_is_better(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);
        $this->firestore->seed('discounts', [
            // Descuento automático 50% (aplica a todo) -> gana sobre el cupón 10%.
            ['id' => 'auto', 'code' => 'AUTO50', 'active' => true,
                'discount_type' => 'percentage', 'value' => 50, 'applies_to' => 'all'],
            ['id' => 'cup', 'code' => 'CHICO10', 'active' => true,
                'discount_type' => 'percentage', 'value' => 10,
                'applies_to' => 'products', 'applicable_ids' => ['otro']],
        ]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
            'shipping_address' => 'x',
            'payment_method' => 'cash',
            'discount_code' => 'CHICO10',
        ], $headers)->assertStatus(201)->assertJson(['data' => [
            'subtotal' => 200,
            'total_amount' => 100, // 50% automático, el cupón 10% se descarta
            'coupon' => null,
        ]]);
    }

    public function test_creating_order_empties_the_user_cart(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
        ]]);

        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
            'shipping_address' => 'x',
            'payment_method' => 'mercado_pago',
        ], $headers)->assertStatus(201);

        // El carrito del usuario queda vacío tras crear la orden.
        $this->assertSame([], $this->firestore->getDocument('carts', 'cart-1')['items']);
    }

    public function test_webhook_with_wrong_amount_keeps_order_unpaid(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);

        $orderId = (string) $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
            'shipping_address' => 'x',
            'payment_method' => 'mercado_pago',
        ], $headers)->json('data.id');

        // external_reference que en el flujo real fija /pay; acá lo seteamos
        // directo para aislar el chequeo de monto del webhook.
        $this->firestore->updateDocument('orders', $orderId, ['external_reference' => $orderId]);

        // MP reporta un monto distinto al total real (200): debe rechazarse.
        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'external_reference' => $orderId,
                'transaction_amount' => 1,
            ]),
        ]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => self::PAYMENT_ID]])
            ->assertStatus(400);

        $this->assertSame('pending', $this->firestore->getDocument('orders', $orderId)['payment_status']);
    }

    public function test_approved_payment_decrements_stock_once(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);
        $this->firestore->seed('orders', [[
            'id' => 'o-stock',
            'external_reference' => 'ref-stock',
            'total_amount' => 300,
            'status' => 'pending',
            'payment_status' => 'pending',
            'items' => [['product_id' => 'p1', 'quantity' => 3]],
        ]]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'ref-stock',
                'transaction_amount' => 300,
            ]),
        ]);

        // Primer webhook: 10 - 3 = 7
        $this->postWebhook(['type' => 'payment', 'data' => ['id' => self::PAYMENT_ID]])
            ->assertStatus(200);
        $this->assertSame(7, $this->firestore->getDocument('products', 'p1')['stock']);
        $this->assertTrue($this->firestore->getDocument('orders', 'o-stock')['stock_decremented']);

        // Reintento de MP: NO debe volver a descontar (sigue en 7).
        $this->postWebhook(['type' => 'payment', 'data' => ['id' => self::PAYMENT_ID]])
            ->assertStatus(200);
        $this->assertSame(7, $this->firestore->getDocument('products', 'p1')['stock']);
    }

    public function test_webhook_approval_does_not_override_fulfillment_status(): void
    {
        $this->firestore->seed('orders', [[
            'id' => 'o-shipped',
            'external_reference' => 'ref-shipped',
            'total_amount' => 200,
            'status' => 'shipped',
            'payment_status' => 'pending',
        ]]);

        Http::fake([
            'api.mercadopago.com/v1/payments/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'ref-shipped',
                'transaction_amount' => 200,
            ]),
        ]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => self::PAYMENT_ID]])
            ->assertStatus(200);

        $order = $this->firestore->getDocument('orders', 'o-shipped');
        $this->assertSame('approved', $order['payment_status']);
        // No retrocede a "confirmed": el estado de envío manda.
        $this->assertSame('shipped', $order['status']);
    }
}
