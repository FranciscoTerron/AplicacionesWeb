<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    // --- LOGIN ---

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $this->firestore->seed('users', [[
            'id' => 'admin-1',
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'active' => true,
        ]]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'id' => 'admin-1',
                    'email' => 'admin@test.com',
                    'role' => 'admin',
                ],
            ])
            ->assertJsonStructure(['success', 'token', 'user' => ['id', 'name', 'email', 'role']]);

        // El token emitido quedó persistido (hasheado) en api_tokens.
        $this->assertCount(1, $this->firestore->all('api_tokens'));
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $this->firestore->seed('users', [[
            'id' => 'admin-1',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'active' => true,
        ]]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Credenciales inválidas']);
    }

    public function test_login_fails_for_unknown_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@test.com',
            'password' => 'whatever',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Credenciales inválidas']);
    }

    public function test_login_fails_for_inactive_user(): void
    {
        $this->firestore->seed('users', [[
            'id' => 'admin-1',
            'email' => 'admin@test.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'active' => false,
        ]]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_validation_errors(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['email' => 'not-an-email', 'password' => 'x'])
            ->assertStatus(422);
    }

    // --- REGISTER ---

    public function test_register_creates_client_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Nuevo Cliente',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'user' => ['email' => 'nuevo@test.com', 'role' => 'cliente'],
            ])
            ->assertJsonStructure(['success', 'token', 'user' => ['id', 'name', 'email', 'role']]);

        $this->assertCount(1, $this->firestore->all('clients'));
        $this->assertCount(1, $this->firestore->all('api_tokens'));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        $this->firestore->seed('clients', [[
            'id' => 'c-1',
            'email' => 'existe@test.com',
            'active' => true,
        ]]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Otro',
            'email' => 'existe@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'El email ya está registrado']);
    }

    public function test_register_validation_errors(): void
    {
        // Password muy corta (min:8) + sin name.
        $this->postJson('/api/v1/auth/register', [
            'email' => 'x@test.com',
            'password' => 'short',
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/register', [])->assertStatus(422);
    }

    // --- REFRESH ---

    public function test_refresh_rotates_token(): void
    {
        // id != name a propósito: el doc debe actualizarse por su id real,
        // no por el campo `name` (que es 'api-token' para todos los tokens).
        $this->firestore->seed('api_tokens', [[
            'id' => 'tok-real',
            'name' => 'api-token',
            'user_id' => 'user-1',
            'token' => hash('sha256', 'old-token'),
        ]]);

        $response = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => 'old-token',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'token']);

        // No debe crear un doc nuevo: rota el existente in-place.
        $tokens = $this->firestore->all('api_tokens');
        $this->assertCount(1, $tokens);
        // El token hasheado cambió (ya no es el viejo).
        $this->assertNotSame(hash('sha256', 'old-token'), $tokens[0]['token']);
    }

    public function test_refresh_requires_token(): void
    {
        $this->postJson('/api/v1/auth/refresh', [])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Refresh token requerido']);
    }

    public function test_refresh_rejects_invalid_token(): void
    {
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => 'does-not-exist'])
            ->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Refresh token inválido']);
    }
}
