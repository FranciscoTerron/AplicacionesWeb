<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    // --- STORE ---

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 1, 'price' => 100]],
            'shipping_address' => 'Calle Falsa 123',
            'payment_method' => 'cash',
        ])->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    public function test_store_creates_order_with_computed_total(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $response = $this->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => 'p1', 'quantity' => 2, 'price' => 100],
                ['product_id' => 'p2', 'quantity' => 1, 'price' => 350],
            ],
            'shipping_address' => 'Calle Falsa 123',
            'payment_method' => 'card',
        ], $headers);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => 'user-1',
                    'total_amount' => 550, // 2*100 + 1*350
                    'status' => 'pending',
                    'payment_status' => 'pending',
                ],
            ]);

        $this->assertCount(1, $this->firestore->all('orders'));
    }

    public function test_store_validation_errors(): void
    {
        $headers = $this->actingAsApiUser();

        // Sin items.
        $this->postJson('/api/v1/orders', [
            'shipping_address' => 'x',
            'payment_method' => 'cash',
        ], $headers)->assertStatus(422);

        // payment_method inválido.
        $this->postJson('/api/v1/orders', [
            'items' => [['product_id' => 'p1', 'quantity' => 1, 'price' => 10]],
            'shipping_address' => 'x',
            'payment_method' => 'bitcoin',
        ], $headers)->assertStatus(422);
    }

    // --- INDEX ---

    public function test_index_returns_only_own_orders(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'pending', 'created_at' => '2026-01-01'],
            ['id' => 'o2', 'user_id' => 'user-2', 'status' => 'pending', 'created_at' => '2026-01-01'],
            ['id' => 'o3', 'user_id' => 'user-1', 'status' => 'shipped', 'created_at' => '2026-02-01'],
        ]);

        $response = $this->getJson('/api/v1/orders', $headers);

        $response->assertStatus(200)->assertJson(['success' => true]);
        $ids = collect($response->json('data'))->pluck('id')->all();
        sort($ids);
        $this->assertSame(['o1', 'o3'], $ids);
    }

    public function test_index_filters_by_status(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'pending', 'created_at' => '2026-01-01'],
            ['id' => 'o2', 'user_id' => 'user-1', 'status' => 'shipped', 'created_at' => '2026-01-02'],
        ]);

        $response = $this->getJson('/api/v1/orders?status=shipped', $headers);

        $response->assertStatus(200);
        $this->assertSame(['o2'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_index_filters_by_date_range(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'pending', 'created_at' => '2026-01-01'],
            ['id' => 'o2', 'user_id' => 'user-1', 'status' => 'pending', 'created_at' => '2026-03-15'],
            ['id' => 'o3', 'user_id' => 'user-1', 'status' => 'pending', 'created_at' => '2026-06-01'],
        ]);

        $response = $this->getJson('/api/v1/orders?date_from=2026-02-01&date_to=2026-04-01', $headers);

        $response->assertStatus(200);
        $this->assertSame(['o2'], collect($response->json('data'))->pluck('id')->all());
    }

    // --- SHOW ---

    public function test_show_returns_own_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'pending', 'total_amount' => 500],
        ]);

        $this->getJson('/api/v1/orders/o1', $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['id' => 'o1', 'total_amount' => 500]]);
    }

    public function test_show_returns_404_for_missing_order(): void
    {
        $headers = $this->actingAsApiUser();

        $this->getJson('/api/v1/orders/nope', $headers)
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_show_hides_other_users_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-2', 'status' => 'pending'],
        ]);

        // IDOR: no debe revelar que existe ni mostrarla.
        $this->getJson('/api/v1/orders/o1', $headers)->assertStatus(404);
    }

    // --- CANCEL ---

    public function test_cancel_pending_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'pending'],
        ]);

        $this->putJson('/api/v1/orders/o1/cancel', [], $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['status' => 'cancelled']]);
    }

    public function test_cancel_rejects_non_cancelable_status(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-1', 'status' => 'shipped'],
        ]);

        $this->putJson('/api/v1/orders/o1/cancel', [], $headers)
            ->assertStatus(400)
            ->assertJson(['success' => false]);
    }

    public function test_cancel_hides_other_users_order(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('orders', [
            ['id' => 'o1', 'user_id' => 'user-2', 'status' => 'pending'],
        ]);

        $this->putJson('/api/v1/orders/o1/cancel', [], $headers)->assertStatus(404);
    }
}
