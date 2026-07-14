"use client";

import Image from "next/image";
import Link from "next/link";
import { useState } from "react";
import { Minus, Plus, Trash2, Loader2, Tag } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Separator } from "@/components/ui/separator";
import { RequireAuth } from "@/components/require-auth";
import { useCart } from "@/context/cart-context";
import {
  useEnrichedCart,
  cartBaseSubtotal,
  cartAutoDiscount,
} from "@/hooks/use-enriched-cart";
import { validateDiscount } from "@/lib/endpoints";
import { formatPrice } from "@/lib/utils";
import type { Discount } from "@/types/api";

function CartContent() {
  const { items, update, remove } = useCart();
  const { enriched, loading } = useEnrichedCart(items);
  const [code, setCode] = useState("");
  const [discount, setDiscount] = useState<Discount | null>(null);
  const [applying, setApplying] = useState(false);

  // El subtotal es siempre sobre precio base: el cupón y el descuento
  // automático de cada producto NO se suman, gana el que más baja el total
  // (misma regla que aplica el backend al crear la orden).
  const baseSubtotal = cartBaseSubtotal(enriched);
  const autoDiscount = cartAutoDiscount(enriched);
  const couponAmount = discount
    ? discount.discount_type === "percentage"
      ? (baseSubtotal * discount.value) / 100
      : Math.min(discount.value, baseSubtotal)
    : 0;
  const bestDiscount = Math.max(autoDiscount, couponAmount);
  const total = baseSubtotal - bestDiscount;

  async function applyDiscount() {
    if (!code.trim()) return;
    setApplying(true);
    try {
      const d = await validateDiscount(code.trim());
      setDiscount(d);
      // Persistir el cupón para que el checkout lo mande al crear la orden.
      localStorage.setItem("discount_code", d.code);
      toast.success(`Descuento "${d.code}" aplicado`);
    } catch (e) {
      setDiscount(null);
      localStorage.removeItem("discount_code");
      toast.error(e instanceof Error ? e.message : "Código inválido");
    } finally {
      setApplying(false);
    }
  }

  if (loading) {
    return (
      <div className="flex min-h-60 items-center justify-center">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (enriched.length === 0) {
    return (
      <div className="rounded-lg border border-dashed p-12 text-center">
        <p className="text-muted-foreground">Tu carrito está vacío.</p>
        <Button asChild className="mt-4">
          <Link href="/productos">Ver productos</Link>
        </Button>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
      {/* Items */}
      <div className="space-y-3">
        {enriched.map((it) => {
          const p = it.product;
          const img = p?.images?.[0]?.url;
          const itemHasDiscount = p?.has_discount && p.discount;
          const unitPrice = p ? Number(p.final_price ?? p.price) : 0;
          return (
            <div
              key={it.product_id}
              className="flex gap-3 rounded-lg border bg-card p-3"
            >
              <div className="relative size-20 shrink-0 overflow-hidden rounded-md bg-muted">
                {img && (
                  <Image
                    src={img}
                    alt={p?.name ?? ""}
                    fill
                    sizes="80px"
                    className="object-cover"
                  />
                )}
              </div>
              <div className="flex flex-1 flex-col">
                <Link
                  href={`/productos/${it.product_id}`}
                  className="line-clamp-2 text-sm font-medium hover:text-primary"
                >
                  {p?.name ?? "Producto no disponible"}
                </Link>
                {itemHasDiscount ? (
                  <p className="flex items-center gap-1.5 text-sm">
                    <span className="font-medium text-red-600">
                      {formatPrice(p.final_price)}
                    </span>
                    <span className="text-muted-foreground line-through">
                      {formatPrice(p.price)}
                    </span>
                  </p>
                ) : (
                  <p className="text-sm text-muted-foreground">
                    {p ? formatPrice(p.price) : "—"}
                  </p>
                )}
                <div className="mt-auto flex items-center gap-2">
                  <div className="flex items-center rounded-md border">
                    <button
                      className="px-2 py-1 hover:bg-muted disabled:opacity-40"
                      disabled={it.quantity <= 1}
                      onClick={() => update(it.product_id, it.quantity - 1)}
                      aria-label="Restar"
                    >
                      <Minus className="size-3.5" />
                    </button>
                    <span className="min-w-8 text-center text-sm">
                      {it.quantity}
                    </span>
                    <button
                      className="px-2 py-1 hover:bg-muted"
                      onClick={() => update(it.product_id, it.quantity + 1)}
                      aria-label="Sumar"
                    >
                      <Plus className="size-3.5" />
                    </button>
                  </div>
                  <button
                    className="ml-auto flex items-center gap-1 text-sm text-destructive hover:underline"
                    onClick={() => remove(it.product_id)}
                  >
                    <Trash2 className="size-4" /> Quitar
                  </button>
                </div>
              </div>
              <div className="text-right font-semibold">
                {p ? formatPrice(unitPrice * it.quantity) : "—"}
              </div>
            </div>
          );
        })}
      </div>

      {/* Resumen */}
      <div className="h-fit space-y-4 rounded-lg border bg-card p-4">
        <h2 className="font-semibold">Resumen</h2>

        <div className="flex gap-2">
          <Input
            placeholder="Código de descuento"
            value={code}
            onChange={(e) => setCode(e.target.value)}
          />
          <Button
            variant="outline"
            onClick={applyDiscount}
            disabled={applying}
          >
            {applying ? <Loader2 className="animate-spin" /> : <Tag />}
          </Button>
        </div>

        <Separator />

        <div className="space-y-1 text-sm">
          <div className="flex justify-between">
            <span className="text-muted-foreground">Subtotal</span>
            <span>{formatPrice(baseSubtotal)}</span>
          </div>
          {autoDiscount > 0 && (
            <div className="flex justify-between text-green-600">
              <span>Descuento en productos</span>
              <span>-{formatPrice(autoDiscount)}</span>
            </div>
          )}
          {discount && (
            <div className="flex justify-between text-green-600">
              <span>Cupón ({discount.code})</span>
              <span>-{formatPrice(couponAmount)}</span>
            </div>
          )}
          <div className="flex justify-between pt-2 text-base font-bold">
            <span>Total estimado</span>
            <span>{formatPrice(total)}</span>
          </div>
        </div>
        {discount && autoDiscount > couponAmount && (
          <p className="text-xs text-amber-600">
            Tus productos ya tienen un descuento mayor al del cupón; el cupón
            no se aplicará.
          </p>
        )}

        <Button asChild className="w-full" size="lg">
          <Link href="/checkout">Finalizar compra</Link>
        </Button>
        <Button asChild variant="outline" className="w-full">
          <Link href="/productos">Seguir comprando</Link>
        </Button>
        {discount && (
          <p className="text-xs text-muted-foreground">
            El descuento se valida al pagar; el total final lo calcula el
            servidor.
          </p>
        )}
      </div>
    </div>
  );
}

export default function CartPage() {
  return (
    <div className="mx-auto max-w-5xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-bold">Mi carrito</h1>
      <RequireAuth>
        <CartContent />
      </RequireAuth>
    </div>
  );
}
