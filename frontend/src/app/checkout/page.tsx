"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Separator } from "@/components/ui/separator";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { RequireAuth } from "@/components/require-auth";
import { useCart } from "@/context/cart-context";
import { useEnrichedCart, cartSubtotal } from "@/hooks/use-enriched-cart";
import { createOrder } from "@/lib/endpoints";
import { formatPrice } from "@/lib/utils";
import type { PaymentMethod } from "@/types/api";

const PAYMENT_LABELS: Record<PaymentMethod, string> = {
  transfer: "Transferencia bancaria",
  cash: "Efectivo / contra entrega",
  card: "Tarjeta",
};

function CheckoutContent() {
  const { items, remove } = useCart();
  const { enriched, loading } = useEnrichedCart(items);
  const router = useRouter();

  const [address, setAddress] = useState("");
  const [method, setMethod] = useState<PaymentMethod>("transfer");
  const [submitting, setSubmitting] = useState(false);

  const subtotal = cartSubtotal(enriched);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (items.length === 0) {
      toast.error("Tu carrito está vacío");
      return;
    }
    setSubmitting(true);
    try {
      const order = await createOrder({
        items: items.map((i) => ({
          product_id: i.product_id,
          quantity: i.quantity,
        })),
        shipping_address: address,
        payment_method: method,
      });
      // Vaciar carrito tras crear la orden (el backend no lo limpia solo)
      await Promise.all(items.map((i) => remove(i.product_id).catch(() => {})));
      toast.success("¡Orden creada!");
      router.push(`/cuenta/ordenes/${order.id}?creada=1`);
    } catch (err) {
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
          <Select
            value={method}
            onValueChange={(v) => setMethod(v as PaymentMethod)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {(Object.keys(PAYMENT_LABELS) as PaymentMethod[]).map((m) => (
                <SelectItem key={m} value={m}>
                  {PAYMENT_LABELS[m]}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>

      {/* Resumen */}
      <div className="h-fit space-y-4 rounded-lg border bg-card p-4">
        <h2 className="font-semibold">Tu pedido</h2>
        <ul className="space-y-2 text-sm">
          {enriched.map((it) => (
            <li key={it.product_id} className="flex justify-between gap-2">
              <span className="line-clamp-1 text-muted-foreground">
                {it.quantity}× {it.product?.name ?? it.product_id}
              </span>
              <span>
                {it.product
                  ? formatPrice(Number(it.product.price) * it.quantity)
                  : "—"}
              </span>
            </li>
          ))}
        </ul>
        <Separator />
        <div className="flex justify-between text-base font-bold">
          <span>Total</span>
          <span>{formatPrice(subtotal)}</span>
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
