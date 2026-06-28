"use client";

import { useState } from "react";
import { Minus, Plus } from "lucide-react";
import { AddToCartButton } from "./add-to-cart-button";

export function ProductPurchase({
  productId,
  stock,
  outOfStock,
}: {
  productId: string;
  stock: number;
  outOfStock: boolean;
}) {
  const [qty, setQty] = useState(1);
  const max = Math.max(1, stock);

  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
      <div className="flex items-center rounded-lg border">
        <button
          type="button"
          className="px-3 py-2.5 hover:bg-muted disabled:opacity-40"
          disabled={outOfStock || qty <= 1}
          onClick={() => setQty((q) => Math.max(1, q - 1))}
          aria-label="Restar"
        >
          <Minus className="size-4" />
        </button>
        <span className="min-w-10 text-center text-sm font-medium">{qty}</span>
        <button
          type="button"
          className="px-3 py-2.5 hover:bg-muted disabled:opacity-40"
          disabled={outOfStock || qty >= max}
          onClick={() => setQty((q) => Math.min(max, q + 1))}
          aria-label="Sumar"
        >
          <Plus className="size-4" />
        </button>
      </div>
      <AddToCartButton
        productId={productId}
        disabled={outOfStock}
        quantity={qty}
        size="lg"
        className="flex-1"
        label={outOfStock ? "Sin stock" : "Agregar al carrito"}
      />
    </div>
  );
}
