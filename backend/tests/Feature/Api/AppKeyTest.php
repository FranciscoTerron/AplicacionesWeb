<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Verifica el middleware EnsureAppKey (app.client): restringe la API pública al
 * frontend cuando APP_PUBLIC_KEY está seteada, y no exige nada si no lo está.
 */
class AppKeyTest extends TestCase
{
    public function test_sin_key_configurada_no_se_exige(): void
    {
        config(['app.public_api_key' => null]);

        // El middleware deja pasar (el status downstream depende de Firestore;
        // lo que importa es que NO bloquea con 403).
        $response = $this->getJson('/api/v1/health');

        $this->assertNotSame(403, $response->status());
    }

    public function test_con_key_configurada_rechaza_request_sin_header(): void
    {
        config(['app.public_api_key' => 'secret-123']);

        $this->getJson('/api/v1/health')
            ->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    public function test_con_key_configurada_rechaza_header_incorrecto(): void
    {
        config(['app.public_api_key' => 'secret-123']);

        $this->getJson('/api/v1/health', ['X-App-Key' => 'wrong'])
            ->assertStatus(403);
    }

    public function test_con_key_configurada_acepta_header_correcto(): void
    {
        config(['app.public_api_key' => 'secret-123']);

        // Con el header correcto el middleware deja pasar: no hay 403.
        $response = $this->getJson('/api/v1/health', ['X-App-Key' => 'secret-123']);

        $this->assertNotSame(403, $response->status());
    }

    public function test_webhook_exento_de_app_key(): void
    {
        config(['app.public_api_key' => 'secret-123']);

        // El webhook de Mercado Pago no manda X-App-Key: no debe ser rechazado
        // por app.client (maneja su propia validación, devolverá otro status).
        $response = $this->postJson('/api/v1/payments/webhook', []);

        $this->assertNotSame(403, $response->status());
    }
}
