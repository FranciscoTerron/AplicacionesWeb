<?php

namespace Tests\Feature;

use App\Services\HealthCheckService;
use PHPUnit\Framework\Attributes\AllowMockObjectWithoutExpectations;
use Tests\TestCase;

/**
 * Tests para el endpoint publico GET /api/v1/health
 */
#[AllowMockObjectWithoutExpectations]
class HealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $mock = $this->createMock(HealthCheckService::class);
        $mock->method('getStatus')->willReturn([
            'success' => true,
            'message' => 'API working',
            'status' => 'online',
            'timestamp' => now()->toIso8601String(),
            'services' => ['api' => 'online', 'firestore' => 'offline'],
            'version' => '1.0.0',
            'environment' => config('app.env'),
        ]);
        $this->app->instance(HealthCheckService::class, $mock);
    }

    public function test_health_check_returns_200_with_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'API working')
            ->assertJsonPath('data.success', true)
            ->assertJsonPath('data.message', 'API working')
            ->assertJsonPath('data.status', 'online')
            ->assertJsonPath('data.services.api', 'online')
            ->assertJsonPath('data.services.firestore', 'offline')
            ->assertJsonPath('data.version', '1.0.0')
            ->assertJsonPath('data.environment', 'testing');
    }

    public function test_health_check_returns_valid_iso8601_timestamp(): void
    {
        $response = $this->getJson('/api/v1/health');

        $timestamp = $response->json('data.timestamp');

        $this->assertNotFalse(
            @strtotime($timestamp),
            "Timestamp '{$timestamp}' no es una fecha ISO 8601 valida"
        );
    }

    public function test_health_check_response_has_json_content_type(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_health_check_reports_per_service_status(): void
    {
        $response = $this->getJson('/api/v1/health');

        $services = $response->json('data.services');

        $this->assertIsArray($services);
        $this->assertSame('online', $services['api']);
        $this->assertSame('offline', $services['firestore']);
    }

    public function test_health_check_success_flags_are_consistent(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true);
    }
}
