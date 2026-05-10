<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirestoreService;
use Tests\TestCase;

class ShipmentControllerTest extends TestCase
{
    protected FirestoreService $firestoreMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
        $this->firestoreMock = $this->createMock(FirestoreService::class);
        $this->app->instance(FirestoreService::class, $this->firestoreMock);
    }

    protected function mockAuthUser(string $role): void
    {
        $user = new User(['role' => $role, 'id' => '1', 'email' => 'admin@example.com']);
        $this->actingAs($user);
    }

    public function test_index_returns_200(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [
                ['order_id' => 'ord-1', 'tracking_code' => 'TRK1', 'carrier' => 'OCA', 'status' => 'in_transit', 'address' => 'Calle 123', 'active' => true],
            ],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.shipments.index'));

        $response->assertStatus(200);
        $response->assertSee('TRK1');
        $response->assertSee('OCA');
    }

    public function test_index_filters_by_status(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturnCallback(function ($collection) {
            if ($collection === 'shipments') {
                return [
                    'documents' => [
                        ['order_id' => 'ord-1', 'tracking_code' => 'TRACKINGUNICO', 'status' => 'in_transit', 'address' => 'Calle 1'],
                        ['order_id' => 'ord-2', 'tracking_code' => 'OTROTRACKING', 'status' => 'delivered', 'address' => 'Calle 2'],
                    ],
                    'nextPageToken' => null,
                ];
            }

            return ['documents' => [], 'nextPageToken' => null];
        });

        $response = $this->get(route('admin.shipments.index', ['status' => 'in_transit']));

        $response->assertStatus(200);
        $response->assertSee('TRACKINGUNICO');
        $response->assertDontSee('OTROTRACKING');
    }

    public function test_create_returns_view(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('listDocuments')->willReturn([
            'documents' => [],
            'nextPageToken' => null,
        ]);

        $response = $this->get(route('admin.shipments.create'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo Envío', false);
    }

    public function test_store_creates_shipment_with_valid_data(): void
    {
        $this->mockAuthUser('admin');

        $this->firestoreMock->method('getDocument')->willReturn(['client_id' => 'c1', 'total' => 1000]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('createDocument')
            ->with('shipments', $this->anything())
            ->willReturn(['order_id' => 'ord-1']);

        $payload = [
            'order_id' => 'ord-1',
            'address' => 'Calle Falsa 123',
            'status' => 'preparing',
        ];

        $response = $this->post(route('admin.shipments.store'), $payload);

        $response->assertRedirect(route('admin.shipments.index'));
        $response->assertSessionHas('success');
    }

    public function test_show_returns_shipment_details(): void
    {
        $this->mockAuthUser('admin');

        $shipmentId = 'ship-123';
        $shipmentData = [
            'order_id' => 'ord-1',
            'tracking_code' => 'TRK999',
            'status' => 'in_transit',
            'address' => 'Calle Test',
        ];

        $this->firestoreMock
            ->expects($this->exactly(2))
            ->method('getDocument')
            ->with('shipments', $shipmentId)
            ->willReturn($shipmentData);

        $response = $this->get(route('admin.shipments.show', $shipmentId));

        $response->assertStatus(200);
        $response->assertSee('TRK999');
    }

    public function test_destroy_soft_deletes_shipment(): void
    {
        $this->mockAuthUser('admin');

        $shipmentId = 'ship-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['order_id' => 'ord-1', 'active' => true]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('shipments', $shipmentId, $this->callback(function ($data) {
                return isset($data['active']) && $data['active'] === false;
            }));

        $response = $this->delete(route('admin.shipments.destroy', $shipmentId));

        $response->assertRedirect(route('admin.shipments.index'));
        $response->assertSessionHas('success');
    }

    public function test_activate_reactivates_shipment(): void
    {
        $this->mockAuthUser('admin');

        $shipmentId = 'ship-123';

        $this->firestoreMock
            ->expects($this->once())
            ->method('getDocument')
            ->willReturn(['order_id' => 'ord-1', 'active' => false]);

        $this->firestoreMock
            ->expects($this->once())
            ->method('updateDocument')
            ->with('shipments', $shipmentId, $this->callback(function ($data) {
                return isset($data['active']) && $data['active'] === true;
            }));

        $response = $this->post(route('admin.shipments.activate', $shipmentId));

        $response->assertRedirect(route('admin.shipments.index'));
        $response->assertSessionHas('success');
    }

    public function test_activate_forbidden_for_non_admin(): void
    {
        $this->mockAuthUser('cliente');

        $this->firestoreMock->method('getDocument')->willReturn(['order_id' => 'ord-1', 'active' => false]);

        $response = $this->post(route('admin.shipments.activate', 'ship-123'));

        $response->assertStatus(403);
    }
}
