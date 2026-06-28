<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createStub(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_catalog_index_lists_only_active_products(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturnCallback(function ($collection) {
            if ($collection === 'products') {
                return [
                    'documents' => [
                        ['name' => 'ProductoActivo', 'sku' => 'SKU-A', 'price' => 1500, 'stock' => 5, 'active' => true],
                        ['name' => 'ProductoInactivo', 'sku' => 'SKU-I', 'price' => 1000, 'stock' => 0, 'active' => false],
                    ],
                    'nextPageToken' => null,
                ];
            }

            return ['documents' => [], 'nextPageToken' => null];
        });

        $response = $this->get(route('catalog.index'));

        $response->assertStatus(200);
        $response->assertSee('ProductoActivo');
        $response->assertDontSee('ProductoInactivo');
    }

    public function test_catalog_search_filters_products(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturnCallback(function ($collection) {
            if ($collection === 'products') {
                return [
                    'documents' => [
                        ['name' => 'CloroLiquido', 'sku' => 'CLR-1', 'price' => 1500, 'active' => true],
                        ['name' => 'BombaPiscina', 'sku' => 'BMB-1', 'price' => 8000, 'active' => true],
                    ],
                    'nextPageToken' => null,
                ];
            }

            return ['documents' => [], 'nextPageToken' => null];
        });

        $response = $this->get(route('catalog.index', ['search' => 'Cloro']));

        $response->assertStatus(200);
        $response->assertSee('CloroLiquido');
        $response->assertDontSee('BombaPiscina');
    }

    public function test_catalog_show_displays_active_product(): void
    {
        $this->firestoreMock->method('getDocument')->willReturnCallback(function ($collection, $id) {
            if ($collection === 'products' && $id === 'p1') {
                return [
                    'name' => 'CloroLiquido',
                    'sku' => 'CLR-1',
                    'price' => 1500,
                    'stock' => 10,
                    'description' => 'Cloro de alta calidad',
                    'active' => true,
                    'category_id' => 'cat-1',
                ];
            }
            if ($collection === 'categories' && $id === 'cat-1') {
                return ['name' => 'Químicos', 'active' => true];
            }

            return null;
        });

        $response = $this->get(route('catalog.show', 'p1'));

        $response->assertStatus(200);
        $response->assertSee('CloroLiquido');
        $response->assertSee('Químicos');
    }

    public function test_catalog_show_redirects_when_product_inactive(): void
    {
        $this->firestoreMock->method('getDocument')->willReturn([
            'name' => 'ProductoInactivo',
            'active' => false,
        ]);

        $response = $this->get(route('catalog.show', 'p1'));

        $response->assertRedirect(route('catalog.index'));
    }
}
