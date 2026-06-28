<?php

namespace Tests\Unit;

use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeFirestore;

class DiscountServiceTest extends TestCase
{
    private function service(array $discounts = []): DiscountService
    {
        $fake = new FakeFirestore;
        if ($discounts !== []) {
            $fake->seed('discounts', $discounts);
        }

        return new DiscountService($fake);
    }

    public function test_apply_value_percentage(): void
    {
        $this->assertSame(80.0, DiscountService::applyValue(100, 'percentage', 20));
    }

    public function test_apply_value_fixed(): void
    {
        $this->assertSame(70.0, DiscountService::applyValue(100, 'fixed', 30));
    }

    public function test_apply_value_never_negative(): void
    {
        $this->assertSame(0.0, DiscountService::applyValue(100, 'fixed', 500));
    }

    public function test_discount_for_product_returns_discount_when_product_has_discount_id(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'A', 'active' => true, 'discount_type' => 'percentage', 'value' => 10],
        ]);

        $discount = $svc->discountForProduct(['id' => 'p1', 'price' => 100, 'discount_id' => 'd1']);

        $this->assertNotNull($discount);
        $this->assertSame('d1', $discount['id']);
        $this->assertSame('A', $discount['code']);
    }

    public function test_discount_for_product_returns_null_when_no_discount_id(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'A', 'active' => true, 'discount_type' => 'percentage', 'value' => 10],
        ]);

        $discount = $svc->discountForProduct(['id' => 'p1', 'price' => 100]);

        $this->assertNull($discount);
    }

    public function test_discount_for_product_ignores_inactive(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'OFF', 'active' => false, 'discount_type' => 'percentage', 'value' => 50],
        ]);

        $this->assertNull($svc->discountForProduct(['id' => 'p1', 'price' => 100, 'discount_id' => 'd1']));
    }

    public function test_discount_for_product_ignores_used_up(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'MAX', 'active' => true, 'discount_type' => 'percentage', 'value' => 50, 'max_uses' => 5, 'used_count' => 5],
        ]);

        $this->assertNull($svc->discountForProduct(['id' => 'p1', 'price' => 100, 'discount_id' => 'd1']));
    }

    public function test_decorate_with_discount(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'VERANO', 'name' => 'Verano', 'active' => true, 'discount_type' => 'percentage', 'value' => 20],
        ]);

        // Product has discount_id pointing to d1
        $p = $svc->decorate(['id' => 'p1', 'price' => 100, 'discount_id' => 'd1']);

        $this->assertTrue($p['has_discount']);
        $this->assertSame(80.0, $p['final_price']);
        $this->assertSame(20.0, $p['discount_amount']);
        $this->assertSame('VERANO', $p['discount']['code']);
        $this->assertSame(20, $p['discount']['percent_off']);
    }

    public function test_decorate_without_discount(): void
    {
        $svc = $this->service();
        $p = $svc->decorate(['id' => 'p1', 'price' => 100]);

        $this->assertFalse($p['has_discount']);
        $this->assertSame(100.0, $p['final_price']);
        $this->assertSame(0.0, $p['discount_amount']);
        $this->assertNull($p['discount']);
    }
}
