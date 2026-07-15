"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Banknote, Loader2, Wallet } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import { RequireAuth } from "@/components/require-auth";
import { useCart } from "@/context/cart-context";
import {
  useEnrichedCart,
  cartBaseSubtotal,
  cartAutoDiscount,
} from "@/hooks/use-enriched-cart";
import { createOrder, payOrder, validateDiscount } from "@/lib/endpoints";
import { cn, formatPrice } from "@/lib/utils";
import {
  couponAmount as calcCouponAmount,
  bestDiscount as calcBestDiscount,
} from "@/lib/discount";
import type { Discount, PaymentMethod } from "@/types/api";

// Métodos ofrecidos al cliente: solo dos, como botones (no select). "transfer"
// y "card" se dejaron fuera para no confundir; el tipo PaymentMethod los
// mantiene para mostrar órdenes históricas que sí los usaron.
const PAYMENT_OPTIONS: {
  value: PaymentMethod;
  label: string;
  icon: typeof Wallet;
}[] = [
  { value: "mercado_pago", label: "Mercado Pago", icon: Wallet },
  { value: "cash", label: "Efectivo", icon: Banknote },
];

function CheckoutContent() {
  const { items, clearLocal } = useCart();
  const { enriched, loading } = useEnrichedCart(items);
  const router = useRouter();

  const [address, setAddress] = useState("");
  const [method, setMethod] = useState<PaymentMethod>("mercado_pago");
  const [submitting, setSubmitting] = useState(false);
  const [discount, setDiscount] = useState<Discount | null>(null);

  // El cupón se aplicó en el carrito y queda guardado en localStorage; acá lo
  // re-validamos solo para mostrar el mismo desglose en el resumen.
  useEffect(() => {
    const code = localStorage.getItem("discount_code");
    if (!code) return;
    validateDiscount(code)
      .then(setDiscount)
      .catch(() => {
        setDiscount(null);
        localStorage.removeItem("discount_code");
      });
  }, []);

  // Misma regla que el backend: cupón y descuento automático por producto no
  // se suman, gana el que más baja el total.
  const baseSubtotal = cartBaseSubtotal(enriched);
  const autoDiscount = cartAutoDiscount(enriched);
  const couponAmount = calcCouponAmount(discount, baseSubtotal);
  const bestDiscount = calcBestDiscount(autoDiscount, discount, baseSubtotal);
  const total = baseSubtotal - bestDiscount;

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (items.length === 0) {
      toast.error("Tu carrito está vacío");
      return;
    }
    // Mercado Pago se abre en una pestaña nueva. El window.open debe dispararse
    // DENTRO del gesto del click (antes de cualquier await), o el navegador lo
    // bloquea como popup. La pestaña arranca vacía y luego se le setea la URL.
    const mpTab =
      method === "mercado_pago" ? window.open("", "_blank") : null;

    setSubmitting(true);
    try {
      const couponCode = localStorage.getItem("discount_code") ?? undefined;
      const order = await createOrder({
        items: items.map((i) => ({
          product_id: i.product_id,
          quantity: i.quantity,
        })),
        shipping_address: address,
        payment_method: method,
        discount_code: couponCode,
      });
      // Cupón consumido: no debe quedar pegado para la próxima compra.
      localStorage.removeItem("discount_code");

      // Mercado Pago: crear preference y abrir el checkout externo en la pestaña
      // nueva. NO se vacía el carrito todavía: el backend lo limpia recién
      // cuando el webhook acredita el pago. La pestaña actual va al detalle de
      // la orden (pendiente), así no se pierde el contexto del sitio.
      if (method === "mercado_pago") {
        const { init_point } = await payOrder(order.id);
        if (mpTab) {
          mpTab.location.href = init_point;
        } else {
          // Popup bloqueado: caemos a redirigir en la misma pestaña.
          window.location.href = init_point;
          return;
        }
        router.push(`/cuenta/ordenes/${order.id}`);
        return;
      }

      // Métodos sin redirect: el backend ya vació el carrito al crear la orden.
      // Acá solo sincronizamos la UI/badge al instante.
      clearLocal();
      toast.success("¡Orden creada!");
      router.push(`/cuenta/ordenes/${order.id}?creada=1`);
    } catch (err) {
      // Si algo falló, cerramos la pestaña de MP que abrimos en el click.
      mpTab?.close();
      toast.error(err instanceof Error ? err.message : "No se pudo crear la orden");
    } finally {
      setSubmitting(false);
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
        <p className="text-muted-foreground">No hay productos para comprar.</p>
        <Button asChild className="mt-4">
          <Link href="/productos">Ver productos</Link>
        </Button>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]"
    >
      {/* Datos */}
      <div className="space-y-5 rounded-lg border bg-card p-5">
        <div className="space-y-1.5">
          <Label htmlFor="address">Dirección de envío</Label>
          <textarea
            id="address"
            required
            value={address}
            onChange={(e) => setAddress(e.target.value)}
            placeholder="Calle, número, ciudad, código postal..."
            className="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
          />
        </div>

        <div className="space-y-1.5">
          <Label>Método de pago</Label>
          <div className="grid grid-cols-2 gap-3">
            {PAYMENT_OPTIONS.map((opt) => {
              const active = method === opt.value;
              const Icon = opt.icon;
              return (
                <button
                  key={opt.value}
                  type="button"
                  onClick={() => setMethod(opt.value)}
                  aria-pressed={active}
                  className={cn(
                    "flex flex-col items-center justify-center gap-2 rounded-lg border p-4 text-sm font-medium transition-colors",
                    active
                      ? "border-primary bg-primary/5 ring-2 ring-primary"
                      : "border-border hover:bg-accent",
                  )}
                >
                  <Icon className="h-5 w-5" />
                  {opt.label}
                </button>
              );
            })}
          </div>
        </div>
      </div>

      {/* Resumen */}
      <div className="h-fit space-y-4 rounded-lg border bg-card p-4">
        <h2 className="font-semibold">Tu pedido</h2>
        <ul className="space-y-2 text-sm">
          {enriched.map((it) => {
            const p = it.product;
            const unitPrice = p ? Number(p.final_price ?? p.price) : 0;
            return (
              <li key={it.product_id} className="flex justify-between gap-2">
                <span className="line-clamp-1 text-muted-foreground">
                  {it.quantity}× {p?.name ?? it.product_id}
                </span>
                <span>{p ? formatPrice(unitPrice * it.quantity) : "—"}</span>
              </li>
            );
          })}
        </ul>
        <Separator />
        <div className="space-y-1 text-sm">
          <div className="flex justify-between">
            <span className="text-muted-foreground">Subtotal</span>
            <span>{formatPrice(baseSubtotal)}</span>
          </div>
          {autoDiscount > 0 && (
            <div className="flex justify-between text-green-700">
              <span>Descuento en productos</span>
              <span>-{formatPrice(autoDiscount)}</span>
            </div>
          )}
          {discount && (
            <div className="flex justify-between text-green-700">
              <span>Cupón ({discount.code})</span>
              <span>-{formatPrice(couponAmount)}</span>
            </div>
          )}
        </div>
        <Separator />
        <div className="flex justify-between text-base font-bold">
          <span>Total</span>
          <span>{formatPrice(total)}</span>
        </div>
        <Button type="submit" className="w-full" size="lg" disabled={submitting}>
          {submitting && <Loader2 className="animate-spin" />}
          Confirmar pedido
        </Button>
        <p className="text-xs text-muted-foreground">
          El total final lo calcula el servidor según el precio y stock actual.
        </p>
      </div>
    </form>
  );
}

export default function CheckoutPage() {
  return (
    <div className="mx-auto max-w-5xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-bold">Finalizar compra</h1>
      <RequireAuth>
        <CheckoutContent />
      </RequireAuth>
    </div>
  );
}
