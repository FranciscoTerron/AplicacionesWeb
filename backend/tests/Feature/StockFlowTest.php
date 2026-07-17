<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

/**
 * HU-B04: descuento de stock al confirmar órdenes de pago presencial y
 * reposición al cancelar, desde el panel admin (updateStatus).
 */
class StockFlowTest extends TestCase
{
    use InteractsWithApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useFakeFirestore();
        $this->actingAs(new User([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'admin',
            'active' => true,
        ]));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function seedOrderWithProduct(array $overrides = []): void
    {
        $this->firestore->seed('products', [
            ['id' => 'p1', 'name' => 'Cloro', 'price' => 100, 'active' => true, 'stock' => 5],
        ]);
        $this->firestore->seed('orders', [array_merge([
            'id' => 'o1',
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'items' => [['product_id' => 'p1', 'quantity' => 2]],
        ], $overrides)]);
    }

    public function test_confirming_cash_order_decrements_stock_once(): void
    {
        $this->seedOrderWithProduct();

        $this->patch(route('admin.orders.status', 'o1'), ['status' => 'confirmed'])
            ->assertRedirect(route('admin.orders.index'));

        $this->assertSame(3, $this->firestore->getDocument('products', 'p1')['stock']);
        $order = $this->firestore->getDocument('orders', 'o1');
        $this->assertSame('confirmed', $order['status']);
        $this->assertTrue($order['stock_decremented']);

        // Reconfirmar no descuenta de nuevo (idempotente vía la bandera).
        $this->patch(route('admin.orders.status', 'o1'), ['status' => 'confirmed']);
        $this->assertSame(3, $this->firestore->getDocument('products', 'p1')['stock']);
    }

    public function test_confirming_mercado_pago_order_does_not_decrement_stock(): void
    {
        // Para MP el stock lo descuenta el webhook al acreditarse el pago.
        $this->seedOrderWithProduct(['payment_method' => 'mercado_pago']);

        $this->patch(route('admin.orders.status', 'o1'), ['status' => 'confirmed']);

        $this->assertSame(5, $this->firestore->getDocument('products', 'p1')['stock']);
        $this->assertArrayNotHasKey('stock_decremented', $this->firestore->getDocument('orders', 'o1'));
    }

    public function test_cancelling_restores_stock_and_clears_flag(): void
    {
        $this->seedOrderWithProduct(['status' => 'confirmed', 'stock_decremented' => true]);
        $this->firestore->updateDocument('products', 'p1', ['stock' => 3]);

        $this->patch(route('admin.orders.status', 'o1'), ['status' => 'cancelled']);

        $this->assertSame(5, $this->firestore->getDocument('products', 'p1')['stock']);
        $order = $this->firestore->getDocument('orders', 'o1');
        $this->assertSame('cancelled', $order['status']);
        $this->assertFalse($order['stock_decremented']);
        $this->assertArrayNotHasKey('refund_pending', $order);
    }

    public function test_cancelling_paid_order_marks_refund_pending_and_restores_stock(): void
    {
        $this->seedOrderWithProduct([
            'payment_method' => 'mercado_pago',
            'payment_status' => 'approved',
            'status' => 'confirmed',
            'stock_decremented' => true,
        ]);
        $this->firestore->updateDocument('products', 'p1', ['stock' => 3]);

        $this->patch(route('admin.orders.status', 'o1'), ['status' => 'cancelled']);

        $order = $this->firestore->getDocument('orders', 'o1');
        $this->assertSame('cancelled', $order['status']);
        $this->assertTrue($order['refund_pending']);
        $this->assertSame(5, $this->firestore->getDocument('products', 'p1')['stock']);
    }
}
