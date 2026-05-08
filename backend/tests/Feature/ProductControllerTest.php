<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    /**
     * Configuración inicial.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    /**
     * Verifica que el índice de productos retorna 200.
     */
    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock
            ->method('listDocuments')
            ->willReturnMap([
                ['products', 20, null, 'name', [
                    'documents' => [
                        ['id' => 'prod-1', 'name' => 'Cloro 1L', 'price' => 1500, 'stock' => 10, 'active' => true],
                    ],
                    'hasMore' => false,
                    'lastDocumentId' => null,
                ]],
                ['categories', 100, null, 'name', [
                    'documents' => [['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]],
                ]],
                ['subcategories', 100, null, 'name', [
                    'documents' => [],
                ]],
            ]);

        $response = $this->get(route('admin.products.index'));

        $response->assertStatus(200);
    }

    /**
     * Verifica que el formulario de creación redirige al índice (modal-only approach).
     */
    public function test_create_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.products.create'));

        $response->assertRedirect(route('admin.products.index'));
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
            'category_id' => 'cat-1',
            'sku' => 'CLORO-1L',
            'price' => 1500,
            'stock' => 10,
            'min_stock' => 5,
            'active' => '1',
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('products', $this->callback(function ($data) {
                return isset($data['name']) && $data['name'] === 'Cloro 1L' &&
                       $data['active'] === true &&
                       isset($data['created_at']) && isset($data['created_by']);
            }))
            ->willReturn(['id' => 'prod-1', 'name' => 'Cloro 1L']);

        // Category validation
        $this->firestoreMock
            ->method('getDocument')
            ->willReturn(['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]);

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
            'category_id' => 'cat-1',
            'sku' => 'CLORO-1L',
            'price' => 1500,
            'stock' => 10,
            'min_stock' => 5,
            'active' => '1',
        ];

        $this->firestoreMock
            ->method('getDocument')
            ->willReturn(['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]);

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
            'category_id' => 'cat-1',
            'sku' => 'CLORO-1L',
            'price' => '', // requerido
            'stock' => 10,
            'min_stock' => 5,
            'active' => '1',
        ];

        $this->firestoreMock
            ->method('getDocument')
            ->willReturn(['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]);

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
            'category_id' => 'cat-1',
            'sku' => 'CLORO-1L',
            'price' => -100, // negativo - inválido
            'stock' => 10,
            'min_stock' => 5,
            'active' => '1',
        ];

        $this->firestoreMock
            ->method('getDocument')
            ->willReturn(['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]);

        $response = $this->post(route('admin.products.store'), $invalidData);

        $response->assertSessionHasErrors('price');
    }

    /**
     * Verifica que show redirige al índice (modal-only approach).
     */
    public function test_show_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $response = $this->get(route('admin.products.show', $productId));

        $response->assertRedirect(route('admin.products.index'));
    }

    /**
     * Verifica que edit redirige al índice (modal-only approach).
     */
    public function test_edit_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $response = $this->get(route('admin.products.edit', $productId));

        $response->assertRedirect(route('admin.products.index'));
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
            'category_id' => 'cat-1',
            'sku' => 'CLORO-2L',
            'price' => 2500,
            'stock' => 20,
            'min_stock' => 10,
            'active' => '1',
        ];

        // Llamadas esperadas:
        // 1. getModelInstance -> getDocument('products', $id)
        // 2. Validación categoría -> getDocument('categories', $category_id)
        // 3. Dentro de update -> getDocument('products', $id) para verificar existencia
        $this->firestoreMock
            ->expects($this->exactly(3))
            ->method('getDocument')
            ->willReturnMap([
                ['products', $productId, ['id' => $productId, 'name' => 'Cloro 1L', 'category_id' => 'cat-1', 'active' => true]],
                ['categories', 'cat-1', ['id' => 'cat-1', 'name' => 'Limpieza', 'active' => true]],
                ['products', $productId, ['id' => $productId, 'name' => 'Cloro 1L', 'category_id' => 'cat-1', 'active' => true]],
            ]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->callback(function ($data) {
                return isset($data['name']) && $data['name'] === 'Cloro 2L' &&
                       $data['active'] === true &&
                       isset($data['updated_at']) && isset($data['updated_by']);
            }));

        $response = $this->put(route('admin.products.update', $productId), $updateData);

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que destroy elimina un producto (baja lógica).
     */
    public function test_destroy_deletes_product(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['id' => $productId, 'name' => 'Cloro 1L']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->callback(function ($data) {
                return $data['active'] === false && isset($data['updated_at']) && isset($data['updated_by']);
            }));

        $response = $this->delete(route('admin.products.destroy', $productId));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Verifica que activate activa un producto (solo admin).
     */
    public function test_activate_activates_product(): void
    {
        $this->mockAuthUser('admin');

        $productId = 'product-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['id' => $productId, 'name' => 'Cloro 1L', 'active' => false]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('products', $productId, $this->callback(function ($data) {
                return $data['active'] === true && isset($data['updated_at']) && isset($data['updated_by']);
            }));

        $response = $this->post(route('admin.products.activate', $productId));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('success');
    }

    /**
     * Mock de usuario autenticado con rol específico.
     */
    protected function mockAuthUser(string $role): void
    {
        $user = new User(['id' => '1', 'role' => $role]);
        $this->actingAs($user);
    }
}
