<?php

namespace Tests\Feature;

use App\Http\Controllers\DiscountController;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Tests para el CRUD de Descuentos.
 */
class DiscountControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['code' => 'VERANO20', 'discountType' => 'percentage', 'discountValue' => 20, 'active' => true],
            ],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.discounts.index'));

        $response->assertStatus(200);
        $response->assertSee('VERANO20');
    }

    public function test_create_returns_view(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.discounts.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo Descuento');
    }

    public function test_store_creates_discount_with_valid_data(): void
    {
        $this->mockAuthUser('admin');

        $discountData = [
            'code' => 'VERANO20',
            'description' => 'Descuento verano',
            'discountType' => 'percentage',
            'discountValue' => 20,
            'minPurchase' => 1000,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->willReturn(['code' => 'VERANO20']);

        $response = $this->post(route('admin.discounts.store'), $discountData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_show_returns_discount_details(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';
        $discountData = [
            'code' => 'VERANO20',
            'discountType' => 'percentage',
            'discountValue' => 20,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn($discountData);

        $response = $this->get(route('admin.discounts.show', $discountId));

        $response->assertStatus(200);
        $response->assertSee('VERANO20');
    }

    public function test_edit_returns_view_with_existing_data(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';
        $discountData = [
            'code' => 'VERANO20',
            'discountType' => 'percentage',
            'discountValue' => 20,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn($discountData);

        $response = $this->get(route('admin.discounts.edit', $discountId));

        $response->assertStatus(200);
    }

    public function test_update_modifies_existing_discount(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';
        $updateData = [
            'code' => 'VERANO30',
            'discountType' => 'percentage',
            'discountValue' => 30,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['code' => 'VERANO20']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument');

        $response = $this->put(route('admin.discounts.update', $discountId), $updateData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_destroy_deletes_discount(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('deleteDocument');

        $response = $this->delete(route('admin.discounts.destroy', $discountId));

        $response->assertRedirect(route('admin.discounts.index'));
    }

    protected function mockAuthUser(string $role): void
    {
        $authUser = \Mockery::mock();
        $authUser->role = $role;
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);
    }
}