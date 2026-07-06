<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * Tests del webhook de pagos de MercadoPago.
 *
 * Firma según esquema oficial de MP:
 *   header x-signature: "ts=<ts>,v1=<hmac>"
 *   manifest firmado:   id:<data.id>;request-id:<x-request-id>;ts:<ts>;
 */
class PaymentWebhookApiTest extends TestCase
{
    use InteractsWithApi;

    private const SECRET = 'webhook-secret-test';

    private const REQUEST_ID = 'req-123';

    private const TS = '1700000000';

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
        config([
            'services.mercadopago.webhook_secret' => self::SECRET,
            'services.mercadopago.access_token' => 'mp-access-token',
        ]);
    }

    /**
     * Construye una firma válida para un data.id dado.
     */
    private function validSignature(string $dataId): string
    {
        $manifest = 'id:'.$dataId.';request-id:'.self::REQUEST_ID.';ts:'.self::TS.';';
        $v1 = hash_hmac('sha256', $manifest, self::SECRET);

        return 'ts='.self::TS.',v1='.$v1;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function postWebhook(array $body, ?string $signature = null, bool $withRequestId = true): TestResponse
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        if ($signature !== null) {
            $server['HTTP_X_SIGNATURE'] = $signature;
        }
        if ($withRequestId) {
            $server['HTTP_X_REQUEST_ID'] = self::REQUEST_ID;
        }

        return $this->call('POST', '/api/v1/payments/webhook', [], [], [], $server, json_encode($body));
    }

    public function test_without_signature_still_reconciles_via_api(): void
    {
        // La firma es defensa en profundidad; la autenticidad real la da
        // getPayment() (consulta a MP con nuestro token) + validación de monto.
        // Sin firma, igual concilia si el pago verifica contra la API de MP.
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 550,
            ], 200),
        ]);
        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('approved', $this->firestore->all('orders')[0]['payment_status']);
    }

    public function test_tampered_signature_still_reconciles_via_api(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 550,
            ], 200),
        ]);
        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(
            ['type' => 'payment', 'data' => ['id' => 'pay-1']],
            'ts='.self::TS.',v1=deadbeef'
        )->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('approved', $this->firestore->all('orders')[0]['payment_status']);
    }

    public function test_rejects_unsupported_type(): void
    {
        $body = ['type' => 'subscription', 'data' => ['id' => 'pay-1']];

        $this->postWebhook($body, $this->validSignature('pay-1'))
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Tipo de notificación no soportado']);
    }

    public function test_updates_order_when_amount_matches(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 550,
            ], 200),
        ]);

        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], $this->validSignature('pay-1'))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('approved', $this->firestore->all('orders')[0]['payment_status']);
    }

    public function test_rejects_when_amount_does_not_match(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 1, // monto falso
            ], 200),
        ]);

        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], $this->validSignature('pay-1'))
            ->assertStatus(400)
            ->assertJson(['success' => false]);

        // No debe marcar el pago.
        $this->assertSame('pending', $this->firestore->all('orders')[0]['payment_status']);
    }

    public function test_acks_when_no_order_matches(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'approved',
                'external_reference' => 'ref-inexistente',
                'transaction_amount' => 100,
            ], 200),
        ]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], $this->validSignature('pay-1'))
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_rejects_when_payment_id_is_missing(): void
    {
        // type=payment pero sin data.id: no hay pago que consultar.
        $this->postWebhook(['type' => 'payment', 'data' => []], $this->validSignature(''))
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_rejects_when_payment_cannot_be_verified_against_mp(): void
    {
        // MP no devuelve el pago (id inexistente / token inválido): no se
        // concilia nada y se responde 400 para no ACKear un pago fantasma.
        Http::fake([
            'api.mercadopago.com/*' => Http::response('', 404),
        ]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-x']], $this->validSignature('pay-x'))
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_rejected_payment_is_recorded_without_confirming_order(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'rejected',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 550,
            ], 200),
        ]);
        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], $this->validSignature('pay-1'))
            ->assertStatus(200);

        $order = $this->firestore->all('orders')[0];
        $this->assertSame('rejected', $order['payment_status']);
        // Pago rechazado NO confirma la orden ni descuenta stock.
        $this->assertSame('pending', $order['status']);
    }

    public function test_in_process_payment_maps_to_pending(): void
    {
        Http::fake([
            'api.mercadopago.com/*' => Http::response([
                'status' => 'in_process',
                'external_reference' => 'order-ref-1',
                'transaction_amount' => 550,
            ], 200),
        ]);
        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'order-ref-1',
            'total_amount' => 550,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]]);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], $this->validSignature('pay-1'))
            ->assertStatus(200);

        $order = $this->firestore->all('orders')[0];
        $this->assertSame('pending', $order['payment_status']);
        $this->assertSame('pending', $order['status']);
    }
}
