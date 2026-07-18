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
        // HU-B06: add/update validan que el producto exista, esté activo y
        // tenga stock; los tests parten de un producto comprable.
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro granulado', 'price' => 100, 'active' => true, 'stock' => 10],
        ]);
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

    // --- HU-B06: validación de operaciones -------------------------------

    public function test_invalid_action_is_rejected(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->postJson('/api/v1/cart', [
            'action' => 'destroy-everything',
            'product_id' => 'p1',
        ], $headers)->assertStatus(422)->assertJsonValidationErrors(['action']);
    }

    public function test_product_id_is_required_except_for_clear(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->postJson('/api/v1/cart', ['action' => 'add'], $headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);

        $this->postJson('/api/v1/cart', ['action' => 'clear'], $headers)
            ->assertStatus(200);
    }

    public function test_add_rejects_zero_or_negative_quantity(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        foreach ([0, -3] as $quantity) {
            $this->postJson('/api/v1/cart', [
                'action' => 'add',
                'product_id' => 'p1',
                'quantity' => $quantity,
            ], $headers)->assertStatus(422)->assertJsonValidationErrors(['quantity']);
        }
    }

    public function test_add_rejects_missing_or_inactive_product(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p-off', 'name' => 'Descatalogado', 'price' => 50, 'active' => false, 'stock' => 5],
        ]);

        foreach (['ghost', 'p-off'] as $productId) {
            $this->postJson('/api/v1/cart', [
                'action' => 'add',
                'product_id' => $productId,
                'quantity' => 1,
            ], $headers)
                ->assertStatus(422)
                ->assertJson(['success' => false, 'message' => 'El producto no está disponible']);
        }
    }

    public function test_add_rejects_product_without_stock(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('products', [
            ['id' => 'p-agotado', 'name' => 'Lona de invierno', 'price' => 900, 'active' => true, 'stock' => 0],
        ]);

        $this->postJson('/api/v1/cart', [
            'action' => 'add',
            'product_id' => 'p-agotado',
            'quantity' => 1,
        ], $headers)
            ->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Sin stock disponible para: Lona de invierno']);
    }

    public function test_add_caps_quantity_at_available_stock(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 8]],
        ]]);

        // 8 en el carrito + 5 pedidos > stock 10 => queda en 10.
        $this->postJson('/api/v1/cart', [
            'action' => 'add',
            'product_id' => 'p1',
            'quantity' => 5,
        ], $headers)->assertStatus(200)->assertJsonPath('data.items.0.quantity', 10);
    }

    public function test_update_caps_quantity_at_available_stock(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
        ]]);

        $this->postJson('/api/v1/cart', [
            'action' => 'update',
            'product_id' => 'p1',
            'quantity' => 50,
        ], $headers)->assertStatus(200)->assertJsonPath('data.items.0.quantity', 10);
    }

    public function test_update_with_zero_quantity_removes_item(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('carts', [[
            'id' => 'cart-1',
            'user_id' => 'user-1',
            'items' => [
                ['product_id' => 'p1', 'quantity' => 3],
                ['product_id' => 'p2', 'quantity' => 1],
            ],
        ]]);

        $response = $this->postJson('/api/v1/cart', [
            'action' => 'update',
            'product_id' => 'p1',
            'quantity' => 0,
        ], $headers);

        $response->assertStatus(200);
        $items = $response->json('data.items');
        $this->assertCount(1, $items);
        $this->assertSame('p2', $items[0]['product_id']);
    }
}
