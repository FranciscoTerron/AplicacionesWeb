<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('role:admin,editor')->get('/_test/panel', function () {
            return response('ok');
        });
    }

    private function actingAsRole(string $role): void
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => $role,
            'active' => true,
        ]);
        $this->actingAs($user);
    }

    public function test_admin_passes(): void
    {
        $this->actingAsRole('admin');

        $this->get('/_test/panel')->assertOk()->assertSee('ok');
    }

    public function test_editor_passes(): void
    {
        $this->actingAsRole('editor');

        $this->get('/_test/panel')->assertOk()->assertSee('ok');
    }

    public function test_cliente_gets_403(): void
    {
        $this->actingAsRole('cliente');

        $this->get('/_test/panel')->assertStatus(403);
    }

    public function test_guest_gets_403(): void
    {
        $this->get('/_test/panel')->assertStatus(403);
    }

    // HU-B01: un cliente autenticado no puede entrar a ninguna ruta real del panel.

    public function test_cliente_gets_403_on_admin_dashboard(): void
    {
        $this->actingAsRole('cliente');

        $this->get('/admin')->assertStatus(403);
    }

    public function test_cliente_gets_403_on_admin_settings(): void
    {
        $this->actingAsRole('cliente');

        $this->get('/admin/settings')->assertStatus(403);
    }

    public function test_cliente_gets_403_on_csv_export(): void
    {
        $this->actingAsRole('cliente');

        $this->get('/admin/export/orders')->assertStatus(403);
    }

    public function test_editor_can_access_admin_dashboard(): void
    {
        $this->actingAsRole('editor');

        $this->get('/admin')->assertOk();
    }
}
