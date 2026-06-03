<?php

namespace Tests\Feature\Api;

use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * Tests de caracterización del webhook de pagos (estado ACTUAL del código).
 *
 * NOTA: la verificación de firma actual NO sigue el esquema real de
 * MercadoPago (header `x-signature` con `ts=...,v1=...` sobre un manifest).
 * Estos tests fijan el comportamiento vigente para no romperlo sin querer;
 * cuando se corrija la firma, deben actualizarse en el mismo commit.
 */
class PaymentWebhookApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function postWebhook(array $body, ?string $signature = null): TestResponse
    {
        $payload = json_encode($body);

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        if ($signature !== null) {
            $server['HTTP_X_SIGNATURE_SHA256'] = $signature;
        }

        return $this->call('POST', '/api/v1/payments/webhook', [], [], [], $server, $payload);
    }

    public function test_rejects_request_without_signature(): void
    {
        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']])
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Firma inválida']);
    }

    public function test_rejects_invalid_signature(): void
    {
        config(['services.mercadopago.webhook_secret' => 'shh']);

        $this->postWebhook(['type' => 'payment', 'data' => ['id' => 'pay-1']], 'firma-mala')
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Firma inválida']);
    }

    public function test_rejects_unsupported_type_with_valid_signature(): void
    {
        config(['services.mercadopago.webhook_secret' => 'shh']);
        $body = ['type' => 'subscription', 'data' => ['id' => 'pay-1']];
        $sig = hash_hmac('sha256', json_encode($body), 'shh');

        $this->postWebhook($body, $sig)
            ->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Tipo de notificación no soportado']);
    }

    public function test_updates_matching_order_payment_status(): void
    {
        config(['services.mercadopago.webhook_secret' => 'shh']);
        // Sin access_token, getPaymentStatus devuelve 'unknown' sin llamar HTTP.
        config(['services.mercadopago.access_token' => null]);

        $this->firestore->seed('orders', [[
            'id' => 'o1',
            'external_reference' => 'pay-1',
            'payment_status' => 'pending',
        ]]);

        $body = ['type' => 'payment', 'data' => ['id' => 'pay-1']];
        $sig = hash_hmac('sha256', json_encode($body), 'shh');

        $this->postWebhook($body, $sig)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame('unknown', $this->firestore->all('orders')[0]['payment_status']);
    }

    public function test_returns_success_when_no_order_matches(): void
    {
        config(['services.mercadopago.webhook_secret' => 'shh']);

        $body = ['type' => 'payment', 'data' => ['id' => 'pay-inexistente']];
        $sig = hash_hmac('sha256', json_encode($body), 'shh');

        $this->postWebhook($body, $sig)
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
