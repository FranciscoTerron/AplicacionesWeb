<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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
     * HU-B05: la facturación mensual cuenta pagos acreditados (Mercado Pago)
     * y órdenes de efectivo aceptadas, solo del mes corriente, incluyendo
     * documentos con vocabulario legacy.
     */
    public function test_monthly_revenue_counts_approved_and_cash_confirmed_of_current_month(): void
    {
        $thisMonth = now()->startOfMonth()->addDay()->toISOString();
        $lastMonth = now()->subMonthNoOverflow()->toISOString();

        $this->firestore->seed('orders', [
            // Cuenta: MP con pago acreditado este mes.
            ['id' => 'mp-ok', 'status' => 'confirmed', 'payment_status' => 'approved',
                'payment_method' => 'mercado_pago', 'total_amount' => 100, 'created_at' => $thisMonth],
            // Cuenta: efectivo aceptado por el negocio este mes.
            ['id' => 'cash-ok', 'status' => 'confirmed', 'payment_status' => 'pending',
                'payment_method' => 'cash', 'total_amount' => 30, 'created_at' => $thisMonth],
            // Cuenta: legacy (completed + paymentStatus camelCase 'paid').
            ['id' => 'legacy', 'status' => 'completed', 'paymentStatus' => 'paid',
                'payment_method' => 'cash', 'total_amount' => 40, 'created_at' => $thisMonth],
            // No cuenta: pago acreditado pero del mes pasado.
            ['id' => 'mp-viejo', 'status' => 'confirmed', 'payment_status' => 'approved',
                'payment_method' => 'mercado_pago', 'total_amount' => 50, 'created_at' => $lastMonth],
            // No cuenta: MP pendiente de acreditación.
            ['id' => 'mp-pend', 'status' => 'pending', 'payment_status' => 'pending',
                'payment_method' => 'mercado_pago', 'total_amount' => 70, 'created_at' => $thisMonth],
            // No cuenta: cancelada aunque estuvo paga (queda para reembolso).
            ['id' => 'mp-cancel', 'status' => 'cancelled', 'payment_status' => 'approved',
                'payment_method' => 'mercado_pago', 'total_amount' => 60, 'created_at' => $thisMonth],
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('kpis', function (array $kpis) {
            return abs($kpis['monthlyRevenue'] - 170.0) < 0.001;
        });
    }
}
