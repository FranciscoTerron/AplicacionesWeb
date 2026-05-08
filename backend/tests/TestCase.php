<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Auth;
use Mockery;

abstract class TestCase extends BaseTestCase
{
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
