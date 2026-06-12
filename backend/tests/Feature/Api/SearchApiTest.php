<?php

namespace Tests\Feature\Api;

use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
    }

    /**
     * @param  array<int, array<string, mixed>>  $extra
     */
    private function seedProducts(array $extra = []): void
    {
        $this->firestore->seed('products', array_merge([
            ['id' => 'p1', 'name' => 'Cloro Líquido', 'sku' => 'CLR-1', 'price' => 1500, 'active' => true, 'stock' => 10],
            ['id' => 'p2', 'name' => 'Bomba Piscina', 'sku' => 'BMB-1', 'price' => 8000, 'active' => true, 'stock' => 0],
        ], $extra));
    }

    public function test_search_is_public(): void
    {
        $this->seedProducts();

        $this->postJson('/api/v1/catalog/search', ['query' => 'Cloro'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_search_matches_by_query(): void
    {
        $this->seedProducts();

        $response = $this->postJson('/api/v1/catalog/search', ['query' => 'Cloro']);

        $response->assertStatus(200);
        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_excludes_inactive_products(): void
    {
        $this->seedProducts([
            ['id' => 'p3', 'name' => 'Cloro Viejo', 'sku' => 'CLR-OLD', 'price' => 100, 'active' => false, 'stock' => 5],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', ['query' => 'Cloro']);

        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_filters_by_price_range(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'A', 'price' => 1000, 'active' => true],
            ['id' => 'p2', 'name' => 'B', 'price' => 5000, 'active' => true],
            ['id' => 'p3', 'name' => 'C', 'price' => 10000, 'active' => true],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', [
            'min_price' => 2000,
            'max_price' => 8000,
        ]);

        $this->assertSame(['p2'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_filters_in_stock(): void
    {
        $this->seedProducts();

        $response = $this->postJson('/api/v1/catalog/search', ['in_stock' => true]);

        $this->assertSame(['p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_filters_by_category(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'A', 'price' => 100, 'active' => true, 'category_id' => 'cat-1'],
            ['id' => 'p2', 'name' => 'B', 'price' => 200, 'active' => true, 'category_id' => 'cat-2'],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', ['category_id' => 'cat-2']);

        $this->assertSame(['p2'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_sorts_by_price_desc(): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'A', 'price' => 100, 'active' => true],
            ['id' => 'p2', 'name' => 'B', 'price' => 900, 'active' => true],
            ['id' => 'p3', 'name' => 'C', 'price' => 500, 'active' => true],
        ]);

        $response = $this->postJson('/api/v1/catalog/search', [
            'sort_by' => 'price',
            'sort_order' => 'desc',
        ]);

        $this->assertSame(['p2', 'p3', 'p1'], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_search_validation_rejects_bad_sort_field(): void
    {
        $this->postJson('/api/v1/catalog/search', ['sort_by' => 'hacker'])
            ->assertStatus(422);
    }

    public function test_search_validation_rejects_negative_price(): void
    {
        $this->postJson('/api/v1/catalog/search', ['min_price' => -5])
            ->assertStatus(422);
    }
}
