<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthApiControllerTest extends TestCase
{
    public function test_login_endpoint_exists(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Credenciales inválidas',
        ]);
    }

    public function test_login_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
    }

    public function test_register_endpoint_exists(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'token',
            'user' => ['id', 'name', 'email', 'role'],
        ]);
    }

    public function test_register_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422);
    }

    public function test_refresh_endpoint_requires_refresh_token(): void
    {
        $response = $this->postJson('/api/v1/auth/refresh', []);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Refresh token requerido',
        ]);
    }
}
