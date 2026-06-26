<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class OrderPayApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();

        config([
            'services.mercadopago.access_token' => 'TEST-token',
            'services.mercadopago.frontend_url' => 'https://tienda.test',
            'app.url' => 'https://api.test',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedOrder(array $overrides = []): string
    {
        $order = array_merge([
            'id' => 'order-1',
            'user_id' => 'user-1',
            'items' => [
                ['product_id' => 'p1', 'name' => 'Cloro', 'quantity' => 2, 'price' => 100],
            ],
            'total_amount' => 200,
            'status' => 'pending',
            'payment_status' => 'pending',
        ], $overrides);

        $this->firestore->seed('orders', [$order]);

        return (string) $order['id'];
    }

    public function test_pay_requires_authentication(): void
    {
        $this->postJson('/api/v1/orders/order-1/pay')
            ->assertStatus(401);
    }

    public function test_pay_creates_preference_and_persists_external_reference(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mp.test/checkout/pref-123',
            ]),
        ]);

        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $id = $this->seedOrder();

        $response = $this->postJson("/api/v1/orders/{$id}/pay", [], $headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'init_point' => 'https://mp.test/checkout/pref-123',
                    'preference_id' => 'pref-123',
                ],
            ]);

        $order = $this->firestore->getDocument('orders', $id);
        $this->assertSame($id, $order['external_reference']);
        $this->assertSame('pref-123', $order['payment_preference_id']);
    }

    public function test_pay_sends_items_and_external_reference_to_mercado_pago(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-123',
                'init_point' => 'https://mp.test/checkout/pref-123',
            ]),
        ]);

        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $id = $this->seedOrder();

        $this->postJson("/api/v1/orders/{$id}/pay", [], $headers)->assertStatus(200);

        Http::assertSent(function ($request) use ($id) {
            return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $request['external_reference'] === $id
                && $request['items'][0]['unit_price'] === 100.0
                && $request['items'][0]['quantity'] === 2
                && $request['items'][0]['currency_id'] === 'ARS';
        });
    }

    public function test_pay_rejects_order_of_another_user(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $id = $this->seedOrder(['user_id' => 'otro']);

        $this->postJson("/api/v1/orders/{$id}/pay", [], $headers)
            ->assertStatus(403);
    }

    public function test_pay_rejects_non_pending_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $id = $this->seedOrder(['payment_status' => 'approved']);

        $this->postJson("/api/v1/orders/{$id}/pay", [], $headers)
            ->assertStatus(422);
    }

    public function test_pay_returns_404_for_missing_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->postJson('/api/v1/orders/no-existe/pay', [], $headers)
            ->assertStatus(404);
    }

    public function test_pay_returns_502_when_mercado_pago_fails(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response(['error' => 'bad'], 400),
        ]);

        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $id = $this->seedOrder();

        $this->postJson("/api/v1/orders/{$id}/pay", [], $headers)
            ->assertStatus(502);
    }
}
