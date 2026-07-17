<?php

namespace App\Services;

/**
 * Descuento y reposición de stock de una orden (HU-B04).
 *
 * Idempotente vía la bandera `stock_decremented` de la orden: descontar dos
 * veces (doble webhook, doble confirmación) o reponer sin haber descontado
 * son no-ops. Los métodos devuelven los campos a mergear en el update de la
 * orden; el caller es responsable de persistirlos.
 */
class StockService
{
    public function __construct(private readonly FirestoreService $firestore) {}

    /**
     * Resta del stock la cantidad comprada de cada ítem, si la orden aún no
     * lo descontó. El stock nunca baja de 0; producto inexistente se ignora.
     * Si algún producto no tenía stock suficiente, marca `oversold` para
     * revisión manual del admin.
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed> campos a mergear en la orden
     */
    public function decrementForOrder(array $order): array
    {
        if ($order['stock_decremented'] ?? false) {
            return [];
        }

        $oversold = false;

        foreach ($this->items($order) as [$productId, $quantity]) {
            $product = $this->firestore->getDocument('products', $productId);
            if ($product === null) {
                continue;
            }

            $current = (int) ($product['stock'] ?? 0);
            if ($current < $quantity) {
                $oversold = true;
            }

            $this->firestore->updateDocument('products', $productId, [
                'stock' => max(0, $current - $quantity),
                'updated_at' => now()->toISOString(),
            ]);
        }

        $fields = ['stock_decremented' => true];
        if ($oversold) {
            $fields['oversold'] = true;
        }

        return $fields;
    }

    /**
     * Repone el stock de los ítems si la orden lo tenía descontado y limpia
     * la bandera (típico al cancelar).
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed> campos a mergear en la orden
     */
    public function restoreForOrder(array $order): array
    {
        if (! ($order['stock_decremented'] ?? false)) {
            return [];
        }

        foreach ($this->items($order) as [$productId, $quantity]) {
            $product = $this->firestore->getDocument('products', $productId);
            if ($product === null) {
                continue;
            }

            $this->firestore->updateDocument('products', $productId, [
                'stock' => (int) ($product['stock'] ?? 0) + $quantity,
                'updated_at' => now()->toISOString(),
            ]);
        }

        return ['stock_decremented' => false];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return list<array{0: string, 1: int}> pares [product_id, quantity] válidos
     */
    private function items(array $order): array
    {
        $pairs = [];

        foreach ((array) ($order['items'] ?? []) as $item) {
            $productId = (string) ($item['product_id'] ?? '');
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId !== '' && $quantity > 0) {
                $pairs[] = [$productId, $quantity];
            }
        }

        return $pairs;
    }
}
