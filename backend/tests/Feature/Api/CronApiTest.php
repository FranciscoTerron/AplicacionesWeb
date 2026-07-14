<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class CronApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
        config(['services.cron.secret' => 'topsecret']);
    }

    private function seedOrders(): void
    {
        $this->firestore->seed('orders', [
            ['id' => 'vieja', 'status' => 'pending', 'payment_status' => 'pending',
                'created_at' => now()->subHours(72)->toISOString()],
            ['id' => 'reciente', 'status' => 'pending', 'payment_status' => 'pending',
                'created_at' => now()->subHours(2)->toISOString()],
            ['id' => 'pagada', 'status' => 'confirmed', 'payment_status' => 'approved',
                'created_at' => now()->subHours(72)->toISOString()],
        ]);
    }

    public function test_expires_only_old_unpaid_orders_with_valid_secret(): void
    {
        $this->seedOrders();

        $this->getJson('/api/v1/cron/expire-orders', ['Authorization' => 'Bearer topsecret'])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['expired' => 1]]);

        $this->assertSame('cancelled', $this->firestore->getDocument('orders', 'vieja')['status']);
        $this->assertSame('pending', $this->firestore->getDocument('orders', 'reciente')['status']);
        $this->assertSame('confirmed', $this->firestore->getDocument('orders', 'pagada')['status']);
    }

    public function test_rejects_without_secret(): void
    {
        $this->seedOrders();

        $this->getJson('/api/v1/cron/expire-orders')->assertStatus(401);

        // No tocó nada.
        $this->assertSame('pending', $this->firestore->getDocument('orders', 'vieja')['status']);
    }

    public function test_rejects_with_wrong_secret(): void
    {
        $this->getJson('/api/v1/cron/expire-orders', ['Authorization' => 'Bearer otra-cosa'])
            ->assertStatus(401);
    }

    public function test_disabled_when_secret_not_configured(): void
    {
        config(['services.cron.secret' => null]);

        $this->getJson('/api/v1/cron/expire-orders', ['Authorization' => 'Bearer topsecret'])
            ->assertStatus(401);
    }
}
