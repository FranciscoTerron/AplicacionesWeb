<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_search_returns_products(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['name' => 'Cloro Liquido', 'sku' => 'CLR-1', 'price' => 1500, 'active' => true, 'stock' => 10],
                ['name' => 'Bomba Piscina', 'sku' => 'BMB-1', 'price' => 8000, 'active' => true, 'stock' => 5],
            ],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', [
            'query' => 'Cloro',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('data.0.name', 'Cloro Liquido');
    }

    public function test_search_filters_by_price_range(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['name' => 'Producto 1', 'sku' => 'SKU-1', 'price' => 1000, 'active' => true],
                ['name' => 'Producto 2', 'sku' => 'SKU-2', 'price' => 5000, 'active' => true],
                ['name' => 'Producto 3', 'sku' => 'SKU-3', 'price' => 10000, 'active' => true],
            ],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', [
            'min_price' => 2000,
            'max_price' => 8000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.sku', 'SKU-2');
    }

    public function test_search_filters_by_in_stock(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['name' => 'Producto 1', 'sku' => 'SKU-1', 'price' => 1000, 'active' => true, 'stock' => 0],
                ['name' => 'Producto 2', 'sku' => 'SKU-2', 'price' => 5000, 'active' => true, 'stock' => 10],
            ],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', [
            'in_stock' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.sku', 'SKU-2');
    }
}
