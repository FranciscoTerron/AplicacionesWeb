<?php

namespace Tests\Feature;

use App\Services\WebPushSender;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * HU-B13: suscripciones Web Push anónimas.
 */
class PushApiTest extends TestCase
{
    use InteractsWithApi;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->useFakeFirestore();
        config(['services.webpush.public_key' => 'test-public-key']);
    }

    private function subscriptionPayload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => self::ENDPOINT,
            'keys' => [
                'p256dh' => 'p256dh-key',
                'auth' => 'auth-secret',
            ],
        ], $overrides);
    }

    public function test_public_key_endpoint_returns_vapid_key(): void
    {
        $this->getJson('/api/v1/push/public-key')
            ->assertOk()
            ->assertJsonPath('data.public_key', 'test-public-key');
    }

    public function test_public_key_returns_503_when_not_configured(): void
    {
        config(['services.webpush.public_key' => null]);

        $this->getJson('/api/v1/push/public-key')->assertStatus(503);
    }

    public function test_subscribe_stores_subscription(): void
    {
        $this->postJson('/api/v1/push/subscribe', $this->subscriptionPayload())
            ->assertOk();

        $docs = $this->firestore->all('push_subscriptions');
        $this->assertCount(1, $docs);
        $this->assertSame(self::ENDPOINT, $docs[0]['endpoint']);
        $this->assertSame('p256dh-key', $docs[0]['p256dh']);
        $this->assertSame('auth-secret', $docs[0]['auth']);
        $this->assertSame(WebPushSender::subscriptionId(self::ENDPOINT), $docs[0]['id']);
    }

    public function test_subscribe_twice_with_same_endpoint_does_not_duplicate(): void
    {
        $this->postJson('/api/v1/push/subscribe', $this->subscriptionPayload())->assertOk();
        $this->postJson('/api/v1/push/subscribe', $this->subscriptionPayload())->assertOk();

        $this->assertCount(1, $this->firestore->all('push_subscriptions'));
    }

    public function test_subscribe_rejects_invalid_payload(): void
    {
        $this->postJson('/api/v1/push/subscribe', ['endpoint' => self::ENDPOINT])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keys.p256dh', 'keys.auth']);

        $this->postJson('/api/v1/push/subscribe', $this->subscriptionPayload([
            'endpoint' => 'http://inseguro.com/push',
        ]))->assertStatus(422)->assertJsonValidationErrors(['endpoint']);
    }

    public function test_unsubscribe_removes_subscription(): void
    {
        $this->postJson('/api/v1/push/subscribe', $this->subscriptionPayload())->assertOk();

        $this->postJson('/api/v1/push/unsubscribe', ['endpoint' => self::ENDPOINT])
            ->assertOk();

        $this->assertCount(0, $this->firestore->all('push_subscriptions'));
    }
}
