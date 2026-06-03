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
            'applies_to' => 'all',
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'TEST10'], $headers)
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['code' => 'TEST10', 'value' => 10, 'applies_to' => 'all'],
            ])
            // No debe filtrar campos internos.
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
            'id' => 'd1', 'code' => 'OFF', 'active' => false, 'applies_to' => 'all',
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'OFF'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_rejects_expired_discount(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'OLD', 'active' => true, 'applies_to' => 'all',
            'valid_to' => '2020-01-01T00:00:00Z',
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'OLD'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_rejects_not_yet_valid_discount(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'SOON', 'active' => true, 'applies_to' => 'all',
            'valid_from' => '2099-01-01T00:00:00Z',
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'SOON'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_rejects_when_max_uses_reached(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'USED', 'active' => true, 'applies_to' => 'all',
            'max_uses' => 5, 'used_count' => 5,
        ]]);

        $this->postJson('/api/v1/discounts/validate', ['code' => 'USED'], $headers)
            ->assertStatus(404);
    }

    public function test_validate_rejects_when_product_not_applicable(): void
    {
        $headers = $this->actingAsApiUser();
        $this->firestore->seed('discounts', [[
            'id' => 'd1', 'code' => 'PROD', 'active' => true,
            'applies_to' => 'product', 'applicable_ids' => ['p-allowed'],
        ]]);

        $this->postJson('/api/v1/discounts/validate', [
            'code' => 'PROD',
            'product_id' => 'p-other',
        ], $headers)->assertStatus(404);
    }

    public function test_validate_validation_error_without_code(): void
    {
        $headers = $this->actingAsApiUser();

        $this->postJson('/api/v1/discounts/validate', [], $headers)->assertStatus(422);
    }
}
