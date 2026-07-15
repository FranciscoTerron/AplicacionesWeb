<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WebPushSender;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * HU-B13: envío de notificaciones push desde el panel admin.
 */
class NotificationPanelTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useFakeFirestore();
        config(['services.webpush.public_key' => 'test-public-key']);
    }

    private function actingAsPanelUser(string $role): void
    {
        $this->actingAs(new User([
            'name' => 'Panel Test',
            'email' => 'panel@test.com',
            'role' => $role,
            'active' => true,
        ]));
    }

    /** @return MockInterface&WebPushSender */
    private function mockSender(): MockInterface
    {
        $mock = Mockery::mock(WebPushSender::class);
        $this->app->instance(WebPushSender::class, $mock);

        return $mock;
    }

    public function test_admin_sees_notification_form(): void
    {
        $this->actingAsPanelUser('admin');

        $this->get('/admin/notifications')
            ->assertOk()
            ->assertSee('Enviar notificación');
    }

    public function test_editor_cannot_access_notifications(): void
    {
        $this->actingAsPanelUser('editor');

        $this->get('/admin/notifications')->assertForbidden();
    }

    public function test_send_broadcasts_to_subscribers(): void
    {
        $this->actingAsPanelUser('admin');

        $this->mockSender()
            ->shouldReceive('broadcast')
            ->once()
            ->with('Promo', '20% off en cloro', '/productos')
            ->andReturn(['sent' => 3, 'failed' => 0, 'removed' => 1]);

        $this->post('/admin/notifications', [
            'title' => 'Promo',
            'body' => '20% off en cloro',
            'url' => '/productos',
        ])->assertRedirect()->assertSessionHas('success');
    }

    public function test_send_without_subscribers_shows_error(): void
    {
        $this->actingAsPanelUser('admin');

        $this->mockSender()
            ->shouldReceive('broadcast')
            ->once()
            ->andReturn(['sent' => 0, 'failed' => 0, 'removed' => 0]);

        $this->post('/admin/notifications', [
            'title' => 'Promo',
            'body' => 'Sin nadie suscripto',
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_send_validates_input(): void
    {
        $this->actingAsPanelUser('admin');

        $this->from('/admin/notifications')
            ->post('/admin/notifications', [
                'title' => '',
                'body' => '',
                'url' => 'https://evil.com',
            ])
            ->assertRedirect('/admin/notifications')
            ->assertSessionHasErrors(['title', 'body', 'url']);
    }
}
