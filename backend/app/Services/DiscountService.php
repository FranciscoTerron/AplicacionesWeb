<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DiscountService
{
    private ?array $activeCache = null;

    /** @var array<string, array>|null Mapa id => descuento usable (cache para lookup O(1)). */
    private ?array $activeById = null;

    public function __construct(private readonly FirestoreService $firestore) {}

    public function activeDiscounts(): array
    {
        if ($this->activeCache !== null) {
            return $this->activeCache;
        }

        $result = $this->firestore->listDocuments('discounts', 200);

        $this->activeCache = collect($result['documents'] ?? [])
            ->filter(fn (array $d) => $this->isUsable($d))
            ->values()
            ->all();

        return $this->activeCache;
    }

    /**
     * Mapa id => descuento usable, construido una sola vez desde activeDiscounts().
     * Evita el N+1 de un getDocument por producto al decorar listados.
     *
     * @return array<string, array>
     */
    private function activeDiscountsById(): array
    {
        if ($this->activeById !== null) {
            return $this->activeById;
        }

        $map = [];
        foreach ($this->activeDiscounts() as $discount) {
            $id = $discount['id'] ?? null;
            if ($id !== null) {
                $map[$id] = $discount;
            }
        }

        return $this->activeById = $map;
    }

    public function isUsable(array $d): bool
    {
        $active = $d['active'] ?? false;
        if (! is_bool($active)) {
            $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        }
        if (! $active) {
            return false;
        }

        $maxUses = $d['max_uses'] ?? null;
        $usedCount = (int) ($d['used_count'] ?? 0);
        if ($maxUses !== null && $maxUses !== '' && $usedCount >= (int) $maxUses) {
            return false;
        }

        return true;
    }

    public static function applyValue(float $base, string $type, float $value): float
    {
        $final = $type === 'percentage'
            ? $base * (1 - $value / 100)
            : $base - $value;

        return max(0.0, round($final, 2));
    }

    public function couponByCode(string $code): ?array
    {
        if (trim($code) === '') {
            return null;
        }

        $result = $this->firestore->query('discounts', ['code' => $code], 1);
        if ($result === []) {
            return null;
        }

        return $this->isUsable($result[0]) ? $result[0] : null;
    }

    public function couponAmountForSubtotal(array $coupon, float $subtotal): float
    {
        $final = self::applyValue(
            $subtotal,
            (string) ($coupon['discount_type'] ?? 'percentage'),
            (float) ($coupon['value'] ?? 0)
        );

        return round($subtotal - $final, 2);
    }

    public function discountForProduct(array $product): ?array
    {
        $discountId = $product['discount_id'] ?? null;
        if (! $discountId) {
            return null;
        }

        // Resuelve desde el cache de descuentos activos (ya filtrados por isUsable).
        // Antes hacía un getDocument('discounts', $id) por producto => N+1 en cada
        // listado del catálogo. Ahora es un lookup O(1) en memoria.
        return $this->activeDiscountsById()[$discountId] ?? null;
    }

    public function decorate(array $product): array
    {
        $base = (float) ($product['price'] ?? 0);
        $discount = $this->discountForProduct($product);

        $product['price'] = round($base, 2);
        $product['has_discount'] = $discount !== null;

        if ($discount) {
            $value = (float) ($discount['value'] ?? 0);
            $type = (string) ($discount['discount_type'] ?? 'percentage');
            $final = self::applyValue($base, $type, $value);
            $amount = round($base - $final, 2);

            $product['final_price'] = $final;
            $product['discount_amount'] = $amount;
            $product['discount'] = [
                'id' => $discount['id'] ?? null,
                'code' => $discount['code'] ?? null,
                'name' => $discount['name'] ?? null,
                'discount_type' => $discount['discount_type'] ?? null,
                'value' => (float) ($discount['value'] ?? 0),
                'percent_off' => $base > 0 ? (int) round(($amount / $base) * 100) : 0,
            ];
        } else {
            $product['final_price'] = round($base, 2);
            $product['discount_amount'] = 0.0;
            $product['discount'] = null;
        }

        return $product;
    }

    public function decorateMany(iterable $products): Collection
    {
        return collect($products)->map(fn (array $p) => $this->decorate($p))->values();
    }
}
