<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/api/v1/cart')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    public function test_post_requires_authentication(): void
    {
        $this->postJson('/api/v1/cart', ['action' => 'add', 'product_id' => 'p1'])
            ->assertStatus(401);
    }

    public function test_show_returns_empty_cart_for_new_user(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->getJson('/api/v1/cart', $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['user_id' => 'user-1', 'items' => []],
            ]);
    }

    public function test_show_returns_existing_cart(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 3]],
        ]]);

        $response = $this->getJson('/api/v1/cart', $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.product_id', 'p1')
            ->assertJsonPath('data.items.0.quantity', 3);
    }

    public function test_add_creates_cart_with_item(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $response = $this->postJson('/api/v1/cart', [
            'action' => 'add',
            'product_id' => 'p1',
            'quantity' => 2,
        ], $headers);

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.product_id', 'p1')
            ->assertJsonPath('data.items.0.quantity', 2);

        $this->assertCount(1, $this->firestore->all('carts'));
    }

    public function test_add_increments_existing_product_quantity(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 1]],
        ]]);

        $response = $this->postJson('/api/v1/cart', [
            'action' => 'add',
            'product_id' => 'p1',
            'quantity' => 4,
        ], $headers);

        $response->assertStatus(200)->assertJsonPath('data.items.0.quantity', 5);
        // No crea carrito nuevo: sigue siendo uno.
        $this->assertCount(1, $this->firestore->all('carts'));
    }

    public function test_update_sets_quantity(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 1]],
        ]]);

        $this->postJson('/api/v1/cart', [
            'action' => 'update',
            'product_id' => 'p1',
            'quantity' => 9,
        ], $headers)->assertStatus(200)->assertJsonPath('data.items.0.quantity', 9);
    }

    public function test_remove_deletes_product_from_cart(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [
                ['product_id' => 'p1', 'quantity' => 1],
                ['product_id' => 'p2', 'quantity' => 2],
            ],
        ]]);

        $response = $this->postJson('/api/v1/cart', [
            'action' => 'remove',
            'product_id' => 'p1',
        ], $headers);

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('p2', $items[0]['product_id']);
    }
}
