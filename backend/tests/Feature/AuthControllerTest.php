<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_show_login_form_returns_view(): void
    {
        $response = $this->withSession([])->get(route('login'));

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public function test_login_with_valid_credentials(): void
    {
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'admin@example.com', 'password' => 'password'])
            ->andReturn(true);

        $response = $this->withSession([])->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_login_with_inactive_user(): void
    {
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'admin@example.com', 'password' => 'password'])
            ->andReturn(false);

        $response = $this->withSession([])->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
    }

    public function test_logout(): void
    {
        Auth::shouldReceive('logout')->once();
        Auth::shouldReceive('user')->andReturn(null);

        $response = $this->withSession([])->post(route('logout'));

        $response->assertRedirect('/');
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->withSession([])->post(route('login'), []);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors(['email', 'password']);
    }
}
