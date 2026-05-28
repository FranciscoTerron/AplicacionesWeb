<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

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

        $this->firestoreMock->method('fetchForPage')->willReturn([
            'documents' => [
                ['id' => '1', 'code' => 'VERANO20', 'discountType' => 'percentage', 'discountValue' => 20, 'active' => true],
            ],
            'hasMore' => false,
            'lastDocumentId' => null,
        ]);

        $response = $this->get(route('admin.discounts.index'));

        $response->assertStatus(200);
        $response->assertSee('VERANO20');
    }

    public function test_create_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $response = $this->get(route('admin.discounts.create'));

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_store_creates_discount_with_valid_data(): void
    {
        $this->mockAuthUser('admin');

        $discountData = [
            'code' => 'VERANO20',
            'name' => 'Descuento Verano',
            'description' => 'Descuento verano',
            'discount_type' => 'percentage',
            'value' => 20,
            'max_uses' => '',
            'valid_from' => '2024-01-01T00:00',
            'valid_to' => '2024-12-31T23:59',
            'applies_to' => 'all',
            'applicable_ids' => '[]', // JSON string as sent by frontend
            'active' => '1', // String as sent by frontend checkbox
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('discounts', $this->callback(function ($data) {
                return isset($data['code']) && $data['code'] === 'VERANO20' &&
                       is_array($data['applicable_ids']) && $data['applicable_ids'] === [] &&
                       $data['active'] === true;
            }))
            ->willReturn(['code' => 'VERANO20']);

        $response = $this->post(route('admin.discounts.store'), $discountData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_store_with_applicable_ids_creates_discount(): void
    {
        $this->mockAuthUser('admin');

        $discountData = [
            'code' => 'CATEGORY20',
            'name' => 'Descuento Categoría',
            'description' => 'Descuento para categoría específica',
            'discount_type' => 'percentage',
            'value' => 20,
            'max_uses' => '',
            'valid_from' => '2024-01-01T00:00',
            'valid_to' => '2024-12-31T23:59',
            'applies_to' => 'categories',
            'applicable_ids' => '["cat1","cat2"]', // JSON string with values
            'active' => '1',
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('discounts', $this->callback(function ($data) {
                return isset($data['code']) && $data['code'] === 'CATEGORY20' &&
                       is_array($data['applicable_ids']) && $data['applicable_ids'] === ['cat1', 'cat2'] &&
                       $data['active'] === true &&
                       $data['applies_to'] === 'categories';
            }))
            ->willReturn(['code' => 'CATEGORY20']);

        $response = $this->post(route('admin.discounts.store'), $discountData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_update_modifies_discount_with_applicable_ids(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';
        $updateData = [
            'code' => 'UPDATED20',
            'name' => 'Descuento Actualizado',
            'description' => 'Descuento actualizado con categorías',
            'discount_type' => 'percentage',
            'value' => 20,
            'max_uses' => '',
            'valid_from' => '2024-01-01T00:00',
            'valid_to' => '2024-12-31T23:59',
            'applies_to' => 'categories',
            'applicable_ids' => '["cat1","cat2"]', // JSON string as sent by frontend
            'active' => '1',
        ];

        $existingData = [
            'id' => $discountId,
            'code' => 'OLD20',
            'name' => 'Descuento Viejo',
            'applies_to' => 'all',
            'applicable_ids' => [],
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->willReturn($existingData);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('discounts', $discountId, $this->callback(function ($data) {
                return isset($data['code']) && $data['code'] === 'UPDATED20' &&
                       is_array($data['applicable_ids']) && $data['applicable_ids'] === ['cat1', 'cat2'] &&
                       $data['active'] === true &&
                       isset($data['updated_at']) && isset($data['updated_by']);
            }));

        $response = $this->put(route('admin.discounts.update', $discountId), $updateData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_show_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';

        $response = $this->get(route('admin.discounts.show', $discountId));

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_edit_redirects_to_index(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';

        $response = $this->get(route('admin.discounts.edit', $discountId));

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_update_modifies_existing_discount(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';
        $updateData = [
            'code' => 'VERANO30',
            'name' => 'Descuento Verano 30%',
            'description' => 'Descuento actualizado',
            'discount_type' => 'percentage',
            'value' => 30,
            'max_uses' => '',
            'valid_from' => '2024-01-01T00:00',
            'valid_to' => '2024-12-31T23:59',
            'applies_to' => 'all',
            'applicable_ids' => '[]',
            'active' => '1',
        ];

        $existingData = [
            'id' => $discountId,
            'code' => 'VERANO20',
            'name' => 'Descuento Verano 20%',
            'discount_type' => 'percentage',
            'value' => 20,
            'active' => true,
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->willReturn($existingData);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('discounts', $discountId, $this->callback(function ($data) {
                return isset($data['code']) && $data['code'] === 'VERANO30' &&
                       $data['active'] === true &&
                       isset($data['updated_at']) && isset($data['updated_by']);
            }));

        $response = $this->put(route('admin.discounts.update', $discountId), $updateData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_destroy_deletes_discount(): void
    {
        $this->mockAuthUser('admin');

        $discountId = 'discount-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['code' => 'VERANO20']);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument');

        $response = $this->delete(route('admin.discounts.destroy', $discountId));

        $response->assertRedirect(route('admin.discounts.index'));
    }

    public function test_store_with_applicable_ids_as_json_string(): void
    {
        $this->mockAuthUser('admin');

        // Simulate frontend sending applicable_ids as JSON string
        $discountData = [
            'code' => 'CATEGORY20',
            'name' => 'Descuento Categoría',
            'description' => 'Descuento para categoría específica',
            'discount_type' => 'percentage',
            'value' => 20,
            'max_uses' => '',
            'valid_from' => '2024-01-01T00:00',
            'valid_to' => '2024-12-31T23:59',
            'applies_to' => 'categories',
            'applicable_ids' => '["cat1","cat2"]', // JSON string as sent by frontend
            'active' => '1', // String as sent by frontend checkbox
        ];

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->willReturn(['code' => 'CATEGORY20']);

        $response = $this->post(route('admin.discounts.store'), $discountData);

        $response->assertRedirect(route('admin.discounts.index'));
    }

    protected function mockAuthUser(string $role): void
    {
        $authUser = \Mockery::mock(User::class)->makePartial();
        $authUser->role = $role;
        $authUser->shouldReceive('getAuthIdentifier')->andReturn('1');
        Auth::shouldReceive('user')->andReturn($authUser);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('id')->andReturn('1');
    }
}
