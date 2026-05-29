<?php

namespace Tests\Feature;

use App\Services\FirestoreService;
use Tests\TestCase;

class FeaturedProductsControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_featured_products_lists_only_featured_active_products(): void
    {
        $this->firestoreMock->method('listDocuments')->willReturnCallback(function ($collection) {
            if ($collection === 'products') {
                return [
                    'documents' => [
                        ['name' => 'Product1', 'sku' => 'SKU-A', 'price' => 1500, 'active' => true, 'featured' => true],
                        ['name' => 'Product2', 'sku' => 'SKU-B', 'price' => 1000, 'active' => true, 'featured' => false],
                        ['name' => 'Product3', 'sku' => 'SKU-C', 'price' => 2000, 'active' => false, 'featured' => true],
                    ],
                    'nextPageToken' => null,
                ];
            }

            return ['documents' => [], 'nextPageToken' => null];
        });

        $response = $this->getJson('/api/v1/catalog/featured');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonPath('data.0.name', 'Product1');
        $response->assertJsonPath('data.0.sku', 'SKU-A');
    }
}
