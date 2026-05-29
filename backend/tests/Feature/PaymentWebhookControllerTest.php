<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Tests\TestCase;

class PaymentWebhookControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'type' => 'payment',
            'data' => ['id' => 'test-payment-123'],
        ], [
            'x-signature-sha256' => 'invalid-signature',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'message' => 'Firma inválida',
        ]);
    }

    public function test_webhook_rejects_invalid_type(): void
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'type' => 'invalid',
            'data' => ['id' => 'test-payment-123'],
        ], [
            'x-signature-sha256' => hash_hmac('sha256', '{}', config('services.mercadopago.webhook_secret') ?? 'test-secret'),
        ]);

        $response->assertStatus(400);
    }

    public function test_webhook_returns_success_without_signature(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [],
        ]);

        $response = $this->postJson('/api/v1/payments/webhook', [
            'type' => 'payment',
            'data' => ['id' => 'test-payment-123'],
        ]);

        $response->assertStatus(400);
    }
}
