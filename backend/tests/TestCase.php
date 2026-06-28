<?php

namespace Tests;

use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bind un FirestoreService mockeado por defecto para que los tests que
     * tocan rutas con FirestoreService inyectado (HomeController, CatalogController,
     * DashboardController, etc.) no rompan en CI donde no hay credenciales.
     * Los tests que necesitan controlar el mock pueden sobreescribirlo con
     * $this->app->instance(FirestoreService::class, $miMock) en su setUp().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $defaultFirestore = $this->createStub(FirestoreService::class);
        $defaultFirestore->method('listDocuments')->willReturn([
            'documents' => [],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);
        $defaultFirestore->method('getDocument')->willReturn(null);
        $defaultFirestore->method('query')->willReturn([]);
        $this->app->instance(FirestoreService::class, $defaultFirestore);
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $user = Mockery::mock(User::class);

        // Atributos que serán compartidos entre getAttribute y setAttribute
        $attributes = [
            'id' => '1',
            'role' => $role,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'hashed_password',
            'active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];

        $user->shouldReceive('getAuthIdentifier')->andReturn('1');
        $user->shouldReceive('id')->andReturn('1');
        $user->shouldReceive('role')->andReturn($role);

        $user->shouldReceive('getAttribute')
            ->andReturnUsing(function ($key) use (&$attributes) {
                return $attributes[$key] ?? null;
            });

        $user->shouldReceive('setAttribute')
            ->andReturnUsing(function ($key, $value) use (&$attributes) {
                $attributes[$key] = $value;

                return $user;
            });

        // ArrayAccess: Blade y otras vistas usan $user['key'] o isset($user->key) que llama offsetExists/Get.
        $user->shouldReceive('offsetExists')
            ->andReturnUsing(function ($key) use (&$attributes) {
                return array_key_exists($key, $attributes);
            });

        $user->shouldReceive('offsetGet')
            ->andReturnUsing(function ($key) use (&$attributes) {
                return $attributes[$key] ?? null;
            });

        $user->shouldReceive('any')->andReturnSelf();

        // Mock del Facade Auth
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('1');
        Auth::shouldReceive('guest')->andReturn(false);

        // Inyectar en el contenedor para que auth() helper lo use
        $this->app->instance('auth', $user);
    }
}
