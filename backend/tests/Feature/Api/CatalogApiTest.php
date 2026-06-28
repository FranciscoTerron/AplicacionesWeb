<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    // --- GET /catalog/products (CatalogController@products) ---

    public function test_products_lists_only_active(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Activo', 'price' => 100, 'active' => true],
            ['id' => 'p2', 'name' => 'Inactivo', 'price' => 200, 'active' => false],
        ]);

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_products_filters_by_search(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'sku' => 'CLR', 'price' => 100, 'active' => true],
            ['id' => 'p2', 'name' => 'Bomba', 'sku' => 'BMB', 'price' => 200, 'active' => true],
        ]);

        $response = $this->getJson('/api/v1/catalog/products?search=Cloro');

        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_products_filters_by_category(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'A', 'price' => 100, 'active' => true, 'category_id' => 'cat-1'],
            ['id' => 'p2', 'name' => 'B', 'price' => 200, 'active' => true, 'category_id' => 'cat-2'],
        ]);

        $response = $this->getJson('/api/v1/catalog/products?category=cat-1');

        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_products_pagination_reports_correct_total(): void
    {
        $products = [];
        for ($i = 1; $i <= 5; $i++) {
            $products[] = ['id' => 'p'.$i, 'name' => 'Prod '.$i, 'price' => $i * 100, 'active' => true];
        }
        $this->firestore->seed('products', $products);

        // Página 1 (limit 2): total real debe ser 5, no el tamaño del lote.
        $page1 = $this->getJson('/api/v1/catalog/products?page=1&limit=2');
        $page1->assertStatus(200)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3);
        $this->assertCount(2, $page1->json('data'));

        // Página 3: el último producto.
        $page3 = $this->getJson('/api/v1/catalog/products?page=3&limit=2');
        $page3->assertStatus(200);
        $this->assertCount(1, $page3->json('data'));
    }

    // --- GET /catalog/featured (CatalogController@featured) ---

    public function test_featured_lists_only_featured_active(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Destacado', 'price' => 100, 'active' => true, 'featured' => true],
            ['id' => 'p2', 'name' => 'Normal', 'price' => 200, 'active' => true, 'featured' => false],
            ['id' => 'p3', 'name' => 'Destacado Inactivo', 'price' => 300, 'active' => false, 'featured' => true],
        ]);

        $response = $this->getJson('/api/v1/catalog/featured');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    // --- GET /catalog/products/{id} (CatalogApiController@product) ---

    public function test_product_show_returns_active_product(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 1500, 'active' => true],
        ]);

        $this->getJson('/api/v1/catalog/products/p1')
            ->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['id' => 'p1', 'name' => 'Cloro']]);
    }

    public function test_product_show_returns_404_for_inactive(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Oculto', 'price' => 100, 'active' => false],
        ]);

        $this->getJson('/api/v1/catalog/products/p1')->assertStatus(404);
    }

    public function test_product_show_returns_404_for_missing(): void
    {
        $this->getJson('/api/v1/catalog/products/nope')->assertStatus(404);
    }

    // --- GET /catalog/categories (CatalogApiController@categories) ---

    public function test_categories_lists_only_active(): void
    {
        $this->firestore->seed('categories', [
            ['id' => 'c1', 'name' => 'Química', 'active' => true],
            ['id' => 'c2', 'name' => 'Vieja', 'active' => false],
        ]);

        $response = $this->getJson('/api/v1/catalog/categories');

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertSame(['c1'], collect($response->json('data'))->pluck('id')->all());
    }

    // --- Descuentos a nivel producto ---

    public function test_products_expose_final_price_with_discount(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'category_id' => 'c1', 'discount_id' => 'd1'],
        ]);
        $this->firestore->seed('discounts', [
            ['id' => 'd1', 'code' => 'PROD20', 'name' => '20% off', 'active' => true, 'discount_type' => 'percentage', 'value' => 20],
        ]);

        $response = $this->getJson('/api/v1/catalog/products');

        $response->assertStatus(200);
        $p = collect($response->json('data'))->firstWhere('id', 'p1');
        $this->assertTrue($p['has_discount']);
        $this->assertEquals(80, $p['final_price']);
        $this->assertSame(20, $p['discount']['percent_off']);
    }

    public function test_product_detail_without_discount_keeps_base_price(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true],
        ]);

        $response = $this->getJson('/api/v1/catalog/products/p1');

        $response->assertStatus(200);
        $this->assertFalse($response->json('data.has_discount'));
        $this->assertEquals(100, $response->json('data.final_price'));
    }
}
