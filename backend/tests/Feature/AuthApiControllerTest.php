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
}
