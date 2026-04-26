<?php

namespace Tests\Feature;

use App\Http\Controllers\ProductController;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests para el CRUD de Productos.
 * Verifica: index, create, store, show, edit, update, destroy
 */
class ProductControllerTest extends TestCase
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
     * Verifica que el índice de productos retorna 200.
     */
    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['name' => 'Cloro 1L', 'price' => 1500, 'stock' => 10, 'active' => true],
            ],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Cloro 1L');
    }

    /**
     * Verifica que el formulario de creación retorna la vista correcta.
     */
    public function test_create_returns_view(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.products.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo Producto');
    }

    /**
     * Verifica que store crea un producto con datos válidos.
     */
    public function test_store_creates_product_with_valid_data(): void
    {
        $this->mockAuthUser('admin');

        $productData = [
            'name' => 'Cloro 1L',
            'description' => 'Cloro líquido para piscinas',
            'price' => 1500,
            'stock' => 10,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('products', $this->anything())
            ->willReturn(['name' => 'Cloro 1L']);

        $response = $this->post(route('admin.products.store'), $productData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que store falla sin nombre.
     */
    public function test_store_fails_without_name(): void
    {
        $this->mockAuthUser('admin');

        $invalidData = [
            'name' => '',
            'price' => 1500,
            'stock' => 10,
        ];

        $response = $this->post(route('admin.products.store'), $invalidData);

        $response->assertSessionHasErrors('name');
    }

    /**
     * Verifica que store falla sin precio.
     */
    public function test_store_fails_without_price(): void
    {
        $this->mockAuthUser('admin');

        $invalidData = [
            'name' => 'Producto',
            'price' => '', // requerido
            'stock' => 10,
        ];

        $response = $this->post(route('admin.products.store'), $invalidData);

        $response->assertSessionHasErrors('price');
    }

    /**
     * Verifica que store falla con precio negativo.
     */
    public function test_store_fails_with_negative_price(): void
    {
        $this->mockAuthUser('admin');

        $invalidData = [
            'name' => 'Producto',
            'price' => -100, // negativo - inválido
            'stock' => 10,
        ];

        $response = $this->post(route('admin.products.store'), $invalidData);

        $response->assertSessionHasErrors('price');
    }

    /**
     * Verifica que show retorna los detalles de un producto.
     */
    public function test_show_returns_product_details(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';
        $productData = [
            'name' => 'Cloro 1L',
            'description' => 'Cloro líquido',
            'price' => 1500,
            'stock' => 10,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->with('products', $productId)
            ->willReturn($productData);

        $response = $this->get(route('admin.products.show', $productId));

        $response->assertStatus(200);
        $response->assertSee('Cloro 1L');
        $response->assertSee('1500');
    }

    /**
     * Verifica que edit retorna la vista con datos existentes.
     */
    public function test_edit_returns_view_with_existing_data(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';
        $productData = [
            'name' => 'Cloro 1L',
            'price' => 1500,
            'stock' => 10,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->with('products', $productId)
            ->willReturn($productData);

        $response = $this->get(route('admin.products.edit', $productId));

        $response->assertStatus(200);
        $response->assertSee('Editar Producto');
    }

    /**
     * Verifica que update modifica un producto existente.
     */
    public function test_update_modifies_existing_product(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';
        $updateData = [
            'name' => 'Cloro 2L',
            'price' => 2500,
            'stock' => 20,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['name' => 'Cloro 1L']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->anything());

        $response = $this->put(route('admin.products.update', $productId), $updateData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que destroy elimina un producto.
     */
    public function test_destroy_deletes_product(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('deleteDocument')
            ->with('products', $productId);

        $response = $this->delete(route('admin.products.destroy', $productId));

        $response->assertRedirect(route('admin.products.index'));
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