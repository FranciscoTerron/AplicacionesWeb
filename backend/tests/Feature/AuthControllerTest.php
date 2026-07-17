<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    private function mockGoogleUser(string $email): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $googleUser = (new GoogleUser)->map([
            'id' => 'google-123',
            'name' => 'Google User',
            'email' => $email,
            'avatar' => null,
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
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

        $adminUser = (object) ['role' => 'admin'];
        Auth::shouldReceive('user')->andReturn($adminUser);

        $response = $this->withSession([])->post(route('login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin');
    }

    public function test_login_as_cliente_redirects_to_home(): void
    {
        Auth::shouldReceive('attempt')
            ->once()
            ->with(['email' => 'cliente@example.com', 'password' => 'password'])
            ->andReturn(true);

        $clienteUser = (object) ['role' => 'cliente'];
        Auth::shouldReceive('user')->andReturn($clienteUser);

        $response = $this->withSession([])->post(route('login'), [
            'email' => 'cliente@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
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

    // HU-B02: el login con Google no crea usuarios internos para emails desconocidos.

    public function test_google_callback_rejects_unknown_email(): void
    {
        $this->mockGoogleUser('desconocido@gmail.com');

        $firestore = $this->createMock(FirestoreService::class);
        $firestore->method('query')->willReturn([]);
        $firestore->expects($this->never())->method('createDocument');
        $this->app->instance(FirestoreService::class, $firestore);

        $response = $this->withSession([])->get(route('auth.google.callback'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_google_callback_creates_admin_for_admin_email(): void
    {
        $this->mockGoogleUser('boss@example.com');
        config(['services.google.admin_emails' => ['boss@example.com']]);

        $firestore = $this->createMock(FirestoreService::class);
        $firestore->method('query')->willReturn([]);
        $firestore->expects($this->once())
            ->method('createDocument')
            ->with('users', $this->callback(function (array $data) {
                return $data['email'] === 'boss@example.com' && $data['role'] === 'admin';
            }))
            ->willReturn([
                'id' => 'new-admin-id',
                'name' => 'Google User',
                'email' => 'boss@example.com',
                'role' => 'admin',
                'active' => true,
            ]);
        $this->app->instance(FirestoreService::class, $firestore);

        $response = $this->withSession([])->get(route('auth.google.callback'));

        $response->assertRedirect('/admin');
    }

    public function test_google_callback_logs_in_existing_user(): void
    {
        $this->mockGoogleUser('editor@example.com');

        $firestore = $this->createMock(FirestoreService::class);
        $firestore->method('query')->willReturn([[
            'id' => 'existing-id',
            'name' => 'Editor',
            'email' => 'editor@example.com',
            'role' => 'editor',
            'active' => true,
        ]]);
        $firestore->expects($this->never())->method('createDocument');
        $this->app->instance(FirestoreService::class, $firestore);

        $response = $this->withSession([])->get(route('auth.google.callback'));

        $response->assertRedirect('/admin');
    }
}
