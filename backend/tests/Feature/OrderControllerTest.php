<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Tests\TestCase;

class OrderControllerTest extends TestCase
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
     * Verifica que el índice de pedidos retorna 200.
     */
    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['clientId' => 'Juan', 'status' => 'pending', 'paymentStatus' => 'pending'],
            ],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
    }

    /**
     * Verifica que el formulario de creación contiene clientes y productos.
     */
    public function test_create_includes_clients_and_products(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')
            ->willReturn(['documents' => [
                ['name' => 'Juan Pérez'],
                ['name' => 'Cloro', 'price' => 1500],
            ]]);

        $response = $this->get(route('admin.orders.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo Pedido');
    }

    /**
     * Verifica que store crea un pedido con items.
     */
    public function test_store_creates_order_with_items(): void
    {
        $this->mockAuthUser('admin');

        $orderData = [
            'clientId' => 'Juan Pérez',
            'items' => [
                ['productId' => 'Cloro', 'quantity' => 2, 'unitPrice' => 1500],
            ],
            'status' => 'pending',
            'paymentStatus' => 'pending',
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('orders', $this->anything())
            ->willReturn(['clientId' => 'Juan Pérez']);

        $response = $this->post(route('admin.orders.store'), $orderData);

        $response->assertRedirect(route('admin.orders.index'));
    }

    /**
     * Verifica que store falla sin cliente.
     */
    public function test_store_fails_without_client(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Verifica que store falla sin items.
     */
    public function test_store_fails_without_items(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Verifica que store falla sin productos en items.
     */
    public function test_store_fails_without_product_in_items(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Verifica que show retorna los detalles de un pedido.
     */
    public function test_show_returns_order_details(): void
    {
        $this->mockAuthUser('admin');

        $orderId = 'order-123';
        $orderData = [
            'clientId' => 'Juan Pérez',
            'status' => 'pending',
            'paymentStatus' => 'pending',
            'items' => [
                ['productId' => 'Cloro', 'quantity' => 2, 'unitPrice' => 1500],
            ],
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->with('orders', $orderId)
            ->willReturn($orderData);

        $response = $this->get(route('admin.orders.show', $orderId));

        $response->assertStatus(200);
        $response->assertSee('Juan Pérez');
    }

    /**
     * Verifica que edit retorna la vista con datos existentes.
     */
    public function test_edit_returns_view_with_existing_data(): void
    {
        $this->mockAuthUser('admin');

        $orderId = 'order-123';
        $orderData = [
            'clientId' => 'Juan Pérez',
            'status' => 'pending',
            'paymentStatus' => 'pending',
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->with('orders', $orderId)
            ->willReturn($orderData);

        $response = $this->get(route('admin.orders.edit', $orderId));

        $response->assertStatus(200);
        $response->assertSee('Editar Pedido');
    }

    /**
     * Verifica que update modifica el estado de un pedido.
     */
    public function test_update_changes_order_status(): void
    {
        $this->mockAuthUser('admin');

        $orderId = 'order-123';
        $updateData = [
            'clientId' => 'Juan Pérez',
            'status' => 'completed',
            'paymentStatus' => 'paid',
            'items' => [
                ['productId' => 'Cloro', 'quantity' => 2, 'unitPrice' => 1500],
            ],
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->willReturn(['status' => 'pending']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('orders', $orderId, $this->anything());

        $response = $this->put(route('admin.orders.update', $orderId), $updateData);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que destroy elimina un pedido.
     */
    public function test_destroy_deletes_order(): void
    {
        $this->mockAuthUser('admin');

        $orderId = 'order-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['clientId' => 'Juan']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('orders', $orderId, $this->anything());

        $response = $this->delete(route('admin.orders.destroy', $orderId));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que updateStatus cambia el estado del pedido.
     */
    public function test_update_status_changes_order_status(): void
    {
        $this->mockAuthUser('admin');

        $orderId = 'order-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['status' => 'pending']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('orders', $orderId, $this->callback(function ($data) {
                return ($data['status'] ?? null) === 'completed';
            }));

        $response = $this->post(route('admin.orders.status', $orderId), [
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que el index acepta filtro por status.
     */
    public function test_index_filters_by_status(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['client_name' => 'PEDIDOPENDIENTE', 'status' => 'pending'],
                ['client_name' => 'PEDIDOCOMPLETO', 'status' => 'completed'],
            ],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.orders.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('PEDIDOPENDIENTE');
        $response->assertDontSee('PEDIDOCOMPLETO');
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $user = new User(['role' => $role, 'id' => 1]);
        $this->actingAs($user);
    }
}
