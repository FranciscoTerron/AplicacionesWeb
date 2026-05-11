<?php

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('admin')->get('/_test/admin-only', function () {
            return response('ok');
        });
    }

    public function test_admin_passes(): void
    {
        $this->mockAuthUser('admin');

        $this->get('/_test/admin-only')
            ->assertOk()
            ->assertSee('ok');
    }

    public function test_editor_gets_403(): void
    {
        $this->mockAuthUser('editor');

        $this->get('/_test/admin-only')->assertStatus(403);
    }

    public function test_cliente_gets_403(): void
    {
        $this->mockAuthUser('cliente');

        $this->get('/_test/admin-only')->assertStatus(403);
    }
}
