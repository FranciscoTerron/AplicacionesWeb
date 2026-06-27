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

    public function test_applies_to_all(): void
    {
        $svc = $this->service();
        $this->assertTrue($svc->appliesToProduct(['applies_to' => 'all'], ['id' => 'p1']));
    }

    public function test_applies_to_specific_product(): void
    {
        $svc = $this->service();
        $disc = ['applies_to' => 'products', 'applicable_ids' => ['p1', 'p2']];

        $this->assertTrue($svc->appliesToProduct($disc, ['id' => 'p1']));
        $this->assertFalse($svc->appliesToProduct($disc, ['id' => 'p9']));
    }

    public function test_applies_to_category(): void
    {
        $svc = $this->service();
        $disc = ['applies_to' => 'categories', 'applicable_ids' => ['cat1']];

        $this->assertTrue($svc->appliesToProduct($disc, ['id' => 'p1', 'category_id' => 'cat1']));
        $this->assertFalse($svc->appliesToProduct($disc, ['id' => 'p1', 'category_id' => 'cat9']));
    }

    public function test_best_for_product_picks_biggest_discount(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'A', 'active' => true, 'applies_to' => 'all', 'discount_type' => 'percentage', 'value' => 10],
            ['id' => 'd2', 'code' => 'B', 'active' => true, 'applies_to' => 'products', 'applicable_ids' => ['p1'], 'discount_type' => 'percentage', 'value' => 30],
        ]);

        $best = $svc->bestForProduct(['id' => 'p1', 'price' => 100]);

        $this->assertNotNull($best);
        $this->assertSame('d2', $best['discount']['id']);
        $this->assertSame(70.0, $best['final']);
        $this->assertSame(30.0, $best['amount']);
    }

    public function test_best_for_product_ignores_expired(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'OLD', 'active' => true, 'applies_to' => 'all', 'discount_type' => 'percentage', 'value' => 50, 'valid_to' => '2000-01-01T00:00:00Z'],
        ]);

        $this->assertNull($svc->bestForProduct(['id' => 'p1', 'price' => 100]));
    }

    public function test_best_for_product_ignores_inactive(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'OFF', 'active' => false, 'applies_to' => 'all', 'discount_type' => 'percentage', 'value' => 50],
        ]);

        $this->assertNull($svc->bestForProduct(['id' => 'p1', 'price' => 100]));
    }

    public function test_best_for_product_ignores_used_up(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'MAX', 'active' => true, 'applies_to' => 'all', 'discount_type' => 'percentage', 'value' => 50, 'max_uses' => 5, 'used_count' => 5],
        ]);

        $this->assertNull($svc->bestForProduct(['id' => 'p1', 'price' => 100]));
    }

    public function test_decorate_with_discount(): void
    {
        $svc = $this->service([
            ['id' => 'd1', 'code' => 'VERANO', 'name' => 'Verano', 'active' => true, 'applies_to' => 'all', 'discount_type' => 'percentage', 'value' => 20],
        ]);

        $p = $svc->decorate(['id' => 'p1', 'price' => 100]);

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
