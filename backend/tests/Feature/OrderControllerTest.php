<?php

namespace Tests\Feature;

use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Validator;
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

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [
                ['id' => '1', 'clientId' => 'Juan', 'status' => 'pending', 'paymentStatus' => 'pending'],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.orders.index'));

        $response->assertStatus(200);
    }

    /**
     * El alta de pedidos vive en el modal del index, así que /create redirige.
     */
    public function test_create_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.orders.create'));

        $response->assertRedirect(route('admin.orders.index'));
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
     * Las reglas del StoreOrderRequest rechazan el alta sin clientId.
     * (Se valida con el Validator en aislamiento porque withoutMiddleware()
     * salta el ciclo del FormRequest en este suite.)
     */
    public function test_store_fails_without_client(): void
    {
        $rules = (new StoreOrderRequest)->rules();
        $validator = Validator::make([
            'items' => [
                ['productId' => 'Cloro', 'quantity' => 2, 'unitPrice' => 1500],
            ],
            'status' => 'pending',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('clientId', $validator->errors()->messages());
    }

    /**
     * Las reglas rechazan el alta sin items.
     */
    public function test_store_fails_without_items(): void
    {
        $rules = (new StoreOrderRequest)->rules();
        $validator = Validator::make([
            'clientId' => 'Juan Pérez',
            'status' => 'pending',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items', $validator->errors()->messages());
    }

    /**
     * Las reglas rechazan items sin productId.
     */
    public function test_store_fails_without_product_in_items(): void
    {
        $rules = (new StoreOrderRequest)->rules();
        $validator = Validator::make([
            'clientId' => 'Juan Pérez',
            'items' => [
                ['quantity' => 2, 'unitPrice' => 1500],
            ],
            'status' => 'pending',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('items.0.productId', $validator->errors()->messages());
    }

    /**
     * Los detalles del pedido se muestran en el modal del index, así que /show redirige.
     */
    public function test_show_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.orders.show', 'order-123'));

        $response->assertRedirect(route('admin.orders.index'));
    }

    /**
     * La edición vive en el modal del index, así que /edit redirige.
     */
    public function test_edit_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.orders.edit', 'order-123'));

        $response->assertRedirect(route('admin.orders.index'));
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

        $response = $this->patch(route('admin.orders.status', $orderId), [
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

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [
                ['client_name' => 'PEDIDOPENDIENTE', 'status' => 'pending'],
                ['client_name' => 'PEDIDOCOMPLETO', 'status' => 'completed'],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        // Mock listDocuments for clients/products filter dropdowns
        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [],
        ]);

        $response = $this->get(route('admin.orders.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('PEDIDOPENDIENTE');
        $response->assertDontSee('PEDIDOCOMPLETO');
    }

    /**
     * Editor SÍ puede cambiar el status de una orden (OrderPolicy::update permite admin y editor).
     */
    public function test_editor_can_change_order_status(): void
    {
        $this->mockAuthUser('editor');

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

        $response = $this->patch(route('admin.orders.status', $orderId), [
            'status' => 'completed',
        ]);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $user = new User(['role' => $role, 'id' => '1', 'email' => 'admin@example.com']);
        $this->actingAs($user);
    }
}
