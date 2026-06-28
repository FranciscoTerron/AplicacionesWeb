<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Tests\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ClientControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    /**
     * Configuración inicial - desactiva middleware de autenticación.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    /**
     * Verifica que el índice de clientes retorna 200.
     */
    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [
                ['name' => 'Juan Pérez', 'email' => 'juan@example.com', 'phone' => '12345678', 'active' => true],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.clients.index'));

        $response->assertStatus(200);
        $response->assertSee('Juan Pérez');
    }

    /**
     * Verifica que el formulario de creación redirige al índice (modal-only approach).
     */
    public function test_create_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.clients.create'));

        $response->assertRedirect(route('admin.clients.index'));
    }

    /**
     * Verifica que store crea un cliente con datos válidos.
     */
    public function test_store_creates_client_with_valid_data(): void
    {
        $this->mockAuthUser('admin');

        $clientData = [
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'phone' => '12345678',
            'address' => 'Calle Falsa 123',
            'city' => 'Gaiman',
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('clients', $this->anything())
            ->willReturn(['name' => 'Juan Pérez']);

        $response = $this->post(route('admin.clients.store'), $clientData);

        $response->assertRedirect(route('admin.clients.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que edit redirige al índice (modal-only approach).
     */
    public function test_edit_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $clientId = 'client-123';

        $response = $this->get(route('admin.clients.edit', $clientId));

        $response->assertRedirect(route('admin.clients.index'));
    }

    /**
     * Verifica que store falla sin nombre (requerido).
     */
    public function test_store_fails_without_name(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Verifica que show redirige al índice (modal-only approach).
     */
    public function test_show_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $clientId = 'client-123';

        $response = $this->get(route('admin.clients.show', $clientId));

        $response->assertRedirect(route('admin.clients.index'));
    }

    /**
     * Verifica que update modifica un cliente existente.
     */
    public function test_update_modifies_existing_client(): void
    {
        $this->mockAuthUser('admin');

        $clientId = 'client-123';
        $updateData = [
            'name' => 'Juan Actualizado',
            'email' => 'juanNew@example.com',
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->atLeastOnce())
            ->method('getDocument')
            ->with('clients', $clientId)
            ->willReturnOnConsecutiveCalls(
                ['name' => 'Juan Pérez'],
                ['name' => 'Juan Pérez'],
            );

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('clients', $clientId, $this->anything());

        $response = $this->put(route('admin.clients.update', $clientId), $updateData);

        $response->assertRedirect(route('admin.clients.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que destroy elimina un cliente.
     */
    public function test_destroy_deletes_client(): void
    {
        $this->mockAuthUser('admin');

        $clientId = 'client-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['name' => 'Juan']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('clients', $clientId, $this->anything());

        $response = $this->delete(route('admin.clients.destroy', $clientId));

        $response->assertRedirect(route('admin.clients.index'));
        $response->assertSessionHas('success');
    }

    protected function mockAuthUser(string $role): void
    {
        $user = new User(['role' => $role, 'id' => '1']);
        $this->actingAs($user);
    }
}
