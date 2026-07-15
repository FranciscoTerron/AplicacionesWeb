"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams, useSearchParams } from "next/navigation";
import { Loader2, CheckCircle2, ArrowLeft, Clock, XCircle } from "lucide-react";
import { toast } from "sonner";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { RequireAuth } from "@/components/require-auth";
import { getOrder, cancelOrder } from "@/lib/endpoints";
import { formatPrice } from "@/lib/utils";
import {
  CANCELABLE_STATUSES,
  orderStatusLabel,
  orderStatusVariant,
  paymentStatusLabel,
  paymentStatusVariant,
} from "@/lib/order-status";
import type { Order } from "@/types/api";

function OrderDetail() {
  const params = useParams<{ id: string }>();
  const search = useSearchParams();
  const justCreated = search.get("creada") === "1";
  // Retorno de Mercado Pago (Checkout Pro): banner inmediato. El estado real
  // del pago lo fija el webhook; al refrescar se ve el payment_status real.
  const pago = search.get("pago");

  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [cancelling, setCancelling] = useState(false);

  useEffect(() => {
    if (!params.id) return;
    getOrder(params.id)
      .then(setOrder)
      .catch((e) =>
        setError(e instanceof Error ? e.message : "No se encontró la orden")
      )
      .finally(() => setLoading(false));
  }, [params.id]);

  // Al volver de Mercado Pago (?pago=success) el webhook concilia el pago de
  // forma asíncrona. Si la orden sigue "pending", refrescamos solo cada 3s
  // (hasta 5 veces) para mostrar "Pago aprobado" sin recargar a mano.
  useEffect(() => {
    if (pago !== "success" || !params.id) return;
    if (order && order.payment_status !== "pending") return;
    let tries = 0;
    const iv = setInterval(async () => {
      tries++;
      try {
        const fresh = await getOrder(params.id);
        setOrder(fresh);
        if (fresh.payment_status !== "pending" || tries >= 5) clearInterval(iv);
      } catch {
        clearInterval(iv);
      }
    }, 3000);
    return () => clearInterval(iv);
  }, [pago, params.id, order?.payment_status]);

  async function handleCancel() {
    if (!order) return;
    setCancelling(true);
    try {
      const updated = await cancelOrder(order.id);
      setOrder(updated);
      toast.success("Orden cancelada");
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "No se pudo cancelar");
    } finally {
      setCancelling(false);
    }
  }

  if (loading) {
    return (
      <div className="flex min-h-60 items-center justify-center">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-6 text-center text-destructive">
        {error ?? "Orden no encontrada"}
      </div>
    );
  }

  // Una orden con pago acreditado no la cancela el cliente (el backend
  // devuelve 422): el reembolso se gestiona con el negocio.
  const canCancel =
    CANCELABLE_STATUSES.includes(order.status) &&
    order.payment_status !== "approved";

  return (
    <div className="space-y-5">
      {justCreated && !pago && (
        <div className="flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-4 text-green-700">
          <CheckCircle2 className="size-5" />
          <span>¡Tu pedido se creó correctamente! Te avisaremos del pago.</span>
        </div>
      )}

      {pago === "success" && (
        <div className="flex items-center gap-2 rounded-lg border border-green-300 bg-green-50 p-4 text-green-700">
          <CheckCircle2 className="size-5" />
          <span>Pago recibido. Estamos confirmándolo, puede tardar unos minutos.</span>
        </div>
      )}
      {pago === "pending" && (
        <div className="flex items-center gap-2 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-700">
          <Clock className="size-5" />
          <span>Tu pago quedó pendiente. Te avisaremos cuando se acredite.</span>
        </div>
      )}
      {pago === "failure" && (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-destructive">
          <span className="flex items-center gap-2">
            <XCircle className="size-5 shrink-0" />
            El pago fue rechazado. Podés intentar nuevamente desde el carrito.
          </span>
          <Button asChild size="sm" variant="destructive">
            <Link href="/carrito">Ir al carrito</Link>
          </Button>
        </div>
      )}

      <div className="rounded-lg border bg-card p-5">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div>
            <p className="font-mono text-xs text-muted-foreground">
              Orden #{order.id}
            </p>
            <div className="mt-2 flex flex-wrap gap-2">
              <Badge variant={orderStatusVariant(order.status)}>
                {orderStatusLabel(order.status)}
              </Badge>
              <Badge variant={paymentStatusVariant(order.payment_status)}>
                {paymentStatusLabel(order.payment_status)}
              </Badge>
            </div>
          </div>
          {canCancel && (
            <Button
              variant="destructive"
              size="sm"
              onClick={handleCancel}
              disabled={cancelling}
            >
              {cancelling && <Loader2 className="animate-spin" />}
              Cancelar orden
            </Button>
          )}
        </div>

        <Separator className="my-4" />

        <h2 className="mb-2 font-semibold">Productos</h2>
        <ul className="space-y-2 text-sm">
          {order.items?.map((it, i) => (
            <li key={`${it.product_id}-${i}`} className="flex justify-between gap-2">
              <span className="text-muted-foreground">
                {it.quantity}× {it.name}
                {it.discount && (
                  <span className="ml-1.5 text-xs text-green-700">
                    (-{formatPrice(it.discount.amount)} c/u)
                  </span>
                )}
              </span>
              <span className="flex items-center gap-1.5">
                {it.discount && it.base_price !== undefined && (
                  <span className="text-muted-foreground line-through">
                    {formatPrice(it.base_price * it.quantity)}
                  </span>
                )}
                {formatPrice(it.price * it.quantity)}
              </span>
            </li>
          ))}
        </ul>

        <Separator className="my-4" />

        <div className="space-y-1 text-sm">
          {order.subtotal !== undefined && (
            <div className="flex justify-between">
              <span className="text-muted-foreground">Subtotal</span>
              <span>{formatPrice(order.subtotal)}</span>
            </div>
          )}
          {order.coupon ? (
            <div className="flex justify-between text-green-700">
              <span>Cupón ({order.coupon.code})</span>
              <span>-{formatPrice(order.coupon.amount)}</span>
            </div>
          ) : (
            order.discount_total !== undefined &&
            order.discount_total > 0 && (
              <div className="flex justify-between text-green-700">
                <span>Descuento en productos</span>
                <span>-{formatPrice(order.discount_total)}</span>
              </div>
            )
          )}
        </div>

        <Separator className="my-4" />

        <div className="flex justify-between text-base font-bold">
          <span>Total</span>
          <span>{formatPrice(order.total_amount)}</span>
        </div>

        <Separator className="my-4" />

        <div className="space-y-1 text-sm text-muted-foreground">
          <p>
            <span className="font-medium text-foreground">Envío a:</span>{" "}
            {order.shipping_address}
          </p>
          <p>
            <span className="font-medium text-foreground">Pago:</span>{" "}
            {order.payment_method}
          </p>
        </div>
      </div>

      <Button asChild variant="ghost" size="sm">
        <Link href="/cuenta/ordenes">
          <ArrowLeft /> Volver a mis pedidos
        </Link>
      </Button>
    </div>
  );
}

export default function OrderDetailPage() {
  return (
    <div className="mx-auto max-w-2xl px-4 py-6">
      <RequireAuth>
        <OrderDetail />
      </RequireAuth>
    </div>
  );
}
