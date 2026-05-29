<?php

namespace Tests\Feature;

use Tests\TestCase;

class OrderApiControllerTest extends TestCase
{
    public function test_orders_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => 'prod1', 'quantity' => 1, 'price' => 100],
            ],
            'shipping_address' => 'Calle Falsa 123',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthenticated',
        ]);
    }
}
