<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Tests\TestCase;

class CartApiShowTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_get_cart_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/cart');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
    }
}
