<?php

namespace Tests\Feature;

use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    public function test_wishlist_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/wishlist');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
    }

    public function test_store_wishlist_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/wishlist', [
            'product_id' => 'prod-1',
        ]);

        $response->assertStatus(401);
    }
}
