<?php

namespace Tests\Unit;

use App\Support\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_normalizes_legacy_order_statuses(): void
    {
        $this->assertSame('processing', OrderStatus::normalize('in_process'));
        $this->assertSame('delivered', OrderStatus::normalize('completed'));
        $this->assertSame('pending', OrderStatus::normalize('pending'));
        $this->assertSame('cancelled', OrderStatus::normalize('cancelled'));
    }

    public function test_normalizes_legacy_payment_statuses(): void
    {
        $this->assertSame('approved', OrderStatus::normalizePayment('paid'));
        $this->assertSame('approved', OrderStatus::normalizePayment('completed'));
        $this->assertSame('pending', OrderStatus::normalizePayment('overdue'));
        $this->assertSame('rejected', OrderStatus::normalizePayment('failed'));
        $this->assertSame('approved', OrderStatus::normalizePayment('approved'));
    }

    public function test_reads_raw_order_including_camel_case_payment_field(): void
    {
        $this->assertSame('approved', OrderStatus::paymentOf(['paymentStatus' => 'paid']));
        $this->assertSame('rejected', OrderStatus::paymentOf(['payment_status' => 'rejected']));
        $this->assertSame('pending', OrderStatus::paymentOf([]));
        $this->assertSame('processing', OrderStatus::of(['status' => 'in_process']));
        $this->assertSame('pending', OrderStatus::of([]));
    }
}
