<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class DiscountApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    public function test_validate_requires_authentication(): void
    {
        $this->postJson('/api/v1/discounts/validate', ['code' => 'TEST10'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Unauthenticated']);
    }

    public function test_validate_returns_active_discount(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1',
            'code' => 'TEST10',
            'name' => '10% off',
            'discount_type' => 'percentage',
            'value' => 10,
            'active' => true,
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'TEST10'], $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'TEST10', 'value' => 10],
            ])
            ->assertJsonMissingPath('data.used_count')
            ->assertJsonMissingPath('data.max_uses');
    }

    public function test_validate_returns_404_for_unknown_code(): void
    {
        $headers = $this->actingAsApiUser();

        $this->postJson('/api/v1/discounts/validate', ['code' => 'NOPE'], $headers)
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_validate_rejects_inactive_discount(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'OFF', 'active' => false,
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'OFF'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_rejects_when_max_uses_reached(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'USED', 'active' => true,
            'max_uses' => 5, 'used_count' => 5,
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'USED'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_validation_error_without_code(): void
    {
        $headers = $this->actingAsApiUser();

        $this->postJson('/api/v1/discounts/validate', [], $headers)->assertStatus(422);
    }

    public function test_validate_with_product_includes_auto_discount_comparison(): void
    {
        $headers = $this->actingAsApiUser();

        // Product with auto discount
        $this->firestore->seed('products', [[
            'id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 10, 'discount_id' => 'd-auto',
        ]]);

        // Auto discount (applied by product)
        $this->firestore->seed('discounts', [
            [
                'id' => 'd-auto',
                'code' => 'AUTO10',
                'active' => true,
                'discount_type' => 'percentage',
                'value' => 10,
            ],
            [
                'id' => 'd1',
                'code' => 'TEST10',
                'active' => true,
                'discount_type' => 'percentage',
                'value' => 10,
            ],
        ]);

        $response = $this->postJson('/api/v1/discounts/validate', [
            'code' => 'TEST10',
            'product_id' => 'p1',
        ], $headers);

        $response->assertStatus(200);
        // Coupon and auto discount are compared, pricing should exist
        $this->assertNotNull($response->json('data.pricing'));
    }
}
