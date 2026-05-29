<?php

namespace Tests\Feature;

use Tests\TestCase;

class DiscountApiControllerTest extends TestCase
{
    public function test_validate_endpoint_exists(): void
    {
        $response = $this->postJson('/api/v1/discounts/validate', [
            'code' => 'TEST10',
        ]);

        $response->assertStatus(401);
    }

    public function test_validate_discount_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/discounts/validate', [
            'code' => 'TEST10',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated',
            ]);
    }
}
