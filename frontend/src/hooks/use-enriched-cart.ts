"use client";

import { useEffect, useState } from "react";
import { getProduct } from "@/lib/endpoints";
import type { CartItem, EnrichedCartItem, Product } from "@/types/api";

// Toma los items del carrito (id + cantidad) y los enriquece con datos del
// producto (nombre, precio, imagen) que el backend no guarda en el carrito.
export function useEnrichedCart(items: CartItem[]) {
  const [enriched, setEnriched] = useState<EnrichedCartItem[]>([]);
  const [loading, setLoading] = useState(true);

  const key = items.map((i) => `${i.product_id}:${i.quantity}`).join(",");

  useEffect(() => {
    let cancelled = false;
    async function load() {
      if (items.length === 0) {
        setEnriched([]);
        setLoading(false);
        return;
      }
      setLoading(true);
      const cache = new Map<string, Product | null>();
      const result = await Promise.all(
        items.map(async (it) => {
          if (!cache.has(it.product_id)) {
            try {
              cache.set(it.product_id, await getProduct(it.product_id));
            } catch {
              cache.set(it.product_id, null);
            }
          }
          return { ...it, product: cache.get(it.product_id) ?? null };
        })
      );
      if (!cancelled) {
        setEnriched(result);
        setLoading(false);
      }
    }
    load();
    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [key]);

  return { enriched, loading };
}

export function cartSubtotal(items: EnrichedCartItem[]): number {
  return items.reduce((acc, it) => {
    const price = it.product ? Number(it.product.price) : 0;
    return acc + price * it.quantity;
  }, 0);
}
