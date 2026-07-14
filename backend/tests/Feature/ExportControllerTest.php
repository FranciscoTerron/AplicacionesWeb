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
        $this->firestoreMock = $this->createStub(FirestoreService::class);
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

    public function test_export_editor_can_export_catalog_entities(): void
    {
        $this->mockAuthUser('editor');

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.export.csv', 'categories'));

        $response->assertStatus(200);
    }

    public function test_export_editor_cannot_export_sensitive_entities(): void
    {
        $this->mockAuthUser('editor');

        // Editor NO puede exportar clients
        $response = $this->get(route('admin.export.csv', 'clients'));
        $response->assertStatus(403);
    }

    public function test_export_editor_cannot_export_orders(): void
    {
        $this->mockAuthUser('editor');

        $response = $this->get(route('admin.export.csv', 'orders'));
        $response->assertStatus(403);
    }

    public function test_export_editor_cannot_export_shipments(): void
    {
        $this->mockAuthUser('editor');

        $response = $this->get(route('admin.export.csv', 'shipments'));
        $response->assertStatus(403);
    }

    public function test_admin_can_export_all_entities(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        // Admin puede exportar todas las entidades
        foreach (['categories', 'products', 'discounts', 'clients', 'orders', 'shipments'] as $entity) {
            $response = $this->get(route('admin.export.csv', $entity));
            $response->assertStatus(200, "Admin should be able to export {$entity}");
        }
    }

    public function test_export_unauthenticated_user_denied(): void
    {
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
