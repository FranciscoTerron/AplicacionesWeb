<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests para el CRUD de Pedidos.
 * Verifica: index, create, store, show, edit, update, destroy
 */
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
        $this->mockAuthUser('admin');

        $invalidData = [
            'clientId' => '', // requerido
            'items' => [
                ['productId' => 'Cloro', 'quantity' => 2, 'unitPrice' => 1500],
            ],
        ];

        $response = $this->post(route('admin.orders.store'), $invalidData);

        $response->assertSessionHasErrors('clientId');
    }

    /**
     * Verifica que store falla sin items.
     */
    public function test_store_fails_without_items(): void
    {
        $this->mockAuthUser('admin');

        $invalidData = [
            'clientId' => 'Juan',
            'items' => [], // vacío - inválido
        ];

        $response = $this->post(route('admin.orders.store'), $invalidData);

        $response->assertSessionHasErrors('items');
    }

    /**
     * Verifica que store falla sin productos en items.
     */
    public function test_store_fails_without_product_in_items(): void
    {
        $this->mockAuthUser('admin');

        $invalidData = [
            'clientId' => 'Juan',
            'items' => [
                ['productId' => '', 'quantity' => 2, 'unitPrice' => 1500], // producto vacío
            ],
        ];

        $response = $this->post(route('admin.orders.store'), $invalidData);

        $response->assertSessionHasErrors();
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
            ->expects($this->once())
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
            ->expects($this->once())
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
            ->expects($this->once())
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
            ->method('deleteDocument')
            ->with('orders', $orderId);

        $response = $this->delete(route('admin.orders.destroy', $orderId));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $authUser = \Mockery::mock();
        $authUser->role = $role;
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);
    }
}