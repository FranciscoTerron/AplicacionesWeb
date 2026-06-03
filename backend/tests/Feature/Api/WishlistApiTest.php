<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class WishlistApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/wishlist')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/wishlist', ['product_id' => 'p1'])->assertStatus(401);
    }

    public function test_index_returns_empty_wishlist_for_new_user(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->getJson('/api/v1/wishlist', $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['user_id' => 'user-1', 'items' => []]]);
    }

    public function test_store_adds_product(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $response = $this->postJson('/api/v1/wishlist', ['product_id' => 'p1'], $headers);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Producto agregado a wishlist']);
        $this->assertSame(['p1'], $response->json('data.items'));
        $this->assertCount(1, $this->firestore->all('wishlists'));
    }

    public function test_store_does_not_duplicate_existing_product(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('wishlists', [[
            'id' => 'w-1',
            'user_id' => 'user-1',
            'items' => ['p1'],
        ]]);

        $response = $this->postJson('/api/v1/wishlist', ['product_id' => 'p1'], $headers);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Producto ya está en la wishlist']);
        $this->assertSame(['p1'], $response->json('data.items'));
    }

    public function test_store_validation_error(): void
    {
        $headers = $this->actingAsApiUser();

        $this->postJson('/api/v1/wishlist', [], $headers)->assertStatus(422);
    }

    public function test_destroy_removes_product(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);
        $this->firestore->seed('wishlists', [[
            'id' => 'w-1',
            'user_id' => 'user-1',
            'items' => ['p1', 'p2'],
        ]]);

        $this->deleteJson('/api/v1/wishlist/p1', [], $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Producto eliminado de wishlist']);

        $this->assertSame(['p2'], $this->firestore->all('wishlists')[0]['items']);
    }

    public function test_destroy_on_empty_wishlist(): void
    {
        $headers = $this->actingAsApiUser(['id' => 'user-1']);

        $this->deleteJson('/api/v1/wishlist/p1', [], $headers)
            ->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Wishlist vacía']);
    }
}
