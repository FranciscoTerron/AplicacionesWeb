<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    protected function mockAuthUser(string $role = 'admin'): void
    {
        $user = new User([
            'id' => 'test-user-id',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => $role,
            'active' => true,
        ]);
        $this->actingAs($user);
    }

    public function test_export_requires_admin_role(): void
    {
        $this->mockAuthUser('editor');

        $response = $this->get(route('admin.export.csv', 'categories'));

        $response->assertStatus(403);
    }

    public function test_export_returns_csv_with_bom(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [
                [
                    'id' => '1',
                    'name' => 'Piscinas',
                    'slug' => 'piscinas',
                    'description' => 'Categoría de piscinas',
                    'active' => true,
                    'order' => 1,
                    'created_at' => '2026-01-01T00:00:00+00:00',
                    'updated_at' => '2026-01-01T00:00:00+00:00',
                ],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.export.csv', 'categories'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="categories_'.now()->format('Y-m-d').'.csv"');

        $content = $response->getContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('ID,Nombre', $content);
        $this->assertStringContainsString('Piscinas', $content);
    }

    public function test_export_entity_not_found(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get('/admin/export/users/csv');

        $response->assertStatus(404);
    }
}
