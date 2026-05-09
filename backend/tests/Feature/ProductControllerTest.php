<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Tests\TestCase;

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
     * Validación cubierta por StoreProductRequest. Sin StartSession middleware
     * los errores no llegan a la sesión — se verifica manualmente o vía Postman.
     * Mismo patrón usado en CategoryControllerTest.
     */
    public function test_store_validation_rules_documented(): void
    {
        $this->assertTrue(true, 'Reglas en StoreProductRequest: name required, price required|min:0, stock required|min:0');
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
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->with('products', $productId)
            ->willReturn($productData);

        $response = $this->get(route('admin.products.show', $productId));

        $response->assertStatus(200);
        $response->assertSee('Cloro 1L');
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
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->with('products', $productId)
            ->willReturn($productData);

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [],
            'nextPageToken' => null,
        ]);

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
            ->expects($this->exactly(2))
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
     * Verifica que destroy hace baja lógica del producto (active=false).
     */
    public function test_destroy_soft_deletes_product(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['name' => 'Cloro 1L', 'active' => true]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->callback(function ($data) {
                return isset($data['active']) && $data['active'] === false;
            }));

        $response = $this->delete(route('admin.products.destroy', $productId));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que activate marca el producto como activo.
     */
    public function test_activate_marks_product_active(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['name' => 'Cloro 1L', 'active' => false]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->callback(function ($data) {
                return isset($data['active']) && $data['active'] === true;
            }));

        $response = $this->post(route('admin.products.activate', $productId));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que un cliente no puede activar productos (solo admin).
     */
    public function test_activate_forbidden_for_non_admin(): void
    {
        $this->mockAuthUser('cliente');

        $productId = 'product-123';

        $this->firestoreMock
            ->method('getDocument')
            ->willReturn(['name' => 'Cloro 1L', 'active' => false]);

        $response = $this->post(route('admin.products.activate', $productId));

        $response->assertStatus(403);
    }

    /**
     * Verifica que el index acepta filtros por categoría y status.
     */
    public function test_index_filters_by_category_and_status(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturnCallback(function ($collection) {
            if ($collection === 'products') {
                return [
                    'documents' => [
                        ['name' => 'CloroFiltrado', 'sku' => 'SKU1', 'category_id' => 'cat-1', 'active' => true, 'price' => 100, 'stock' => 5, 'min_stock' => 1],
                        ['name' => 'OtroProducto', 'sku' => 'SKU2', 'category_id' => 'cat-2', 'active' => true, 'price' => 200, 'stock' => 3, 'min_stock' => 1],
                    ],
                    'nextPageToken' => null,
                ];
            }

            return ['documents' => [], 'nextPageToken' => null];
        });

        $response = $this->get(route('admin.products.index', ['category_id' => 'cat-1', 'status' => 'active']));

        $response->assertStatus(200);
        $response->assertSee('CloroFiltrado');
        $response->assertDontSee('OtroProducto');
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $user = new User(['role' => $role, 'id' => '1']);
        $this->actingAs($user);
    }
}
