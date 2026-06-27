<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Centraliza la lógica de descuentos: qué descuento aplica a qué producto,
 * cuánto baja el precio y cuál gana cuando hay varios.
 *
 * Reglas de negocio (decididas con el equipo):
 *  - Si varios descuentos aplican a un producto, gana el que MÁS baja el precio.
 *  - Un cupón por código y un descuento automático NO se suman: gana el mejor.
 *  - Un descuento vale solo si está activo, vigente y no agotó sus usos.
 */
class DiscountService
{
    /**
     * Cache por instancia (= por request) de los descuentos utilizables.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $activeCache = null;

    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * Descuentos activos y vigentes. Se trae la colección una sola vez y se
     * filtra en PHP (Firestore REST solo filtra por un campo a la vez).
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeDiscounts(): array
    {
        if ($this->activeCache !== null) {
            return $this->activeCache;
        }

        $result = $this->firestore->listDocuments('discounts', 200);
        $now = Carbon::now();

        $this->activeCache = collect($result['documents'] ?? [])
            ->filter(fn (array $d) => $this->isUsable($d, $now))
            ->values()
            ->all();

        return $this->activeCache;
    }

    /**
     * ¿El descuento está activo, vigente y con usos disponibles?
     *
     * @param  array<string, mixed>  $d
     */
    public function isUsable(array $d, ?Carbon $now = null): bool
    {
        $now ??= Carbon::now();

        $active = $d['active'] ?? false;
        if (! is_bool($active)) {
            $active = filter_var($active, FILTER_VALIDATE_BOOLEAN);
        }
        if (! $active) {
            return false;
        }

        if (! empty($d['valid_from']) && $now->lt(Carbon::parse($d['valid_from']))) {
            return false;
        }

        if (! empty($d['valid_to']) && $now->gt(Carbon::parse($d['valid_to']))) {
            return false;
        }

        $maxUses = $d['max_uses'] ?? null;
        $usedCount = (int) ($d['used_count'] ?? 0);
        if ($maxUses !== null && $maxUses !== '' && $usedCount >= (int) $maxUses) {
            return false;
        }

        return true;
    }

    /**
     * ¿Este descuento aplica a este producto según su scope?
     *
     * @param  array<string, mixed>  $discount
     * @param  array<string, mixed>  $product
     */
    public function appliesToProduct(array $discount, array $product): bool
    {
        $appliesTo = $discount['applies_to'] ?? 'all';

        if ($appliesTo === 'all') {
            return true;
        }

        $ids = $discount['applicable_ids'] ?? [];
        if (! is_array($ids) || $ids === []) {
            return false;
        }

        // Se aceptan singular/plural por compatibilidad con datos viejos.
        if (in_array($appliesTo, ['products', 'product'], true)) {
            return in_array($product['id'] ?? null, $ids, true);
        }

        if (in_array($appliesTo, ['categories', 'category'], true)) {
            return in_array($product['category_id'] ?? null, $ids, true);
        }

        return false;
    }

    /**
     * Aplica un valor de descuento a un precio base. Nunca devuelve negativo.
     */
    public static function applyValue(float $base, string $type, float $value): float
    {
        $final = $type === 'percentage'
            ? $base * (1 - $value / 100)
            : $base - $value;

        return max(0.0, round($final, 2));
    }

    /**
     * Busca un cupón utilizable por su código. Devuelve null si no existe,
     * está inactivo, fuera de vigencia o agotó sus usos.
     *
     * @return array<string, mixed>|null
     */
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

    /**
     * Monto que un cupón descuenta sobre el subtotal completo del carrito.
     *
     * @param  array<string, mixed>  $coupon
     */
    public function couponAmountForSubtotal(array $coupon, float $subtotal): float
    {
        $final = self::applyValue(
            $subtotal,
            (string) ($coupon['discount_type'] ?? 'percentage'),
            (float) ($coupon['value'] ?? 0)
        );

        return round($subtotal - $final, 2);
    }

    /**
     * Mejor descuento automático para un producto: el que deja el precio más bajo.
     *
     * @param  array<string, mixed>  $product
     * @return array{discount: array<string,mixed>, base: float, final: float, amount: float}|null
     */
    public function bestForProduct(array $product): ?array
    {
        $base = (float) ($product['price'] ?? 0);
        if ($base <= 0) {
            return null;
        }

        $best = null;
        $bestFinal = $base;

        foreach ($this->activeDiscounts() as $d) {
            if (! $this->appliesToProduct($d, $product)) {
                continue;
            }

            $value = (float) ($d['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }

            $final = self::applyValue($base, (string) ($d['discount_type'] ?? 'percentage'), $value);
            if ($final < $bestFinal) {
                $bestFinal = $final;
                $best = $d;
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'discount' => $best,
            'base' => round($base, 2),
            'final' => $bestFinal,
            'amount' => round($base - $bestFinal, 2),
        ];
    }

    /**
     * Devuelve el producto con campos de precio listos para mostrar/cobrar.
     *
     * Agrega: price (base), final_price, discount_amount, has_discount y un
     * bloque `discount` con la info mínima del descuento aplicado.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public function decorate(array $product): array
    {
        $base = (float) ($product['price'] ?? 0);
        $best = $this->bestForProduct($product);

        $product['price'] = round($base, 2);
        $product['has_discount'] = $best !== null;
        $product['final_price'] = $best['final'] ?? round($base, 2);
        $product['discount_amount'] = $best['amount'] ?? 0.0;
        $product['discount'] = $best
            ? [
                'id' => $best['discount']['id'] ?? null,
                'code' => $best['discount']['code'] ?? null,
                'name' => $best['discount']['name'] ?? null,
                'discount_type' => $best['discount']['discount_type'] ?? null,
                'value' => (float) ($best['discount']['value'] ?? 0),
                'percent_off' => $base > 0 ? (int) round(($best['amount'] / $base) * 100) : 0,
            ]
            : null;

        return $product;
    }

    /**
     * Decora una colección de productos reutilizando la cache de descuentos.
     *
     * @param  iterable<int, array<string, mixed>>  $products
     * @return Collection<int, array<string, mixed>>
     */
    public function decorateMany(iterable $products): Collection
    {
        return collect($products)->map(fn (array $p) => $this->decorate($p))->values();
    }
}
