"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { Loader2, ChevronRight } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { RequireAuth } from "@/components/require-auth";
import { getOrders } from "@/lib/endpoints";
import { formatPrice } from "@/lib/utils";
import {
  orderStatusLabel,
  orderStatusVariant,
  paymentStatusLabel,
  paymentStatusVariant,
} from "@/lib/order-status";
import type { Order } from "@/types/api";

function OrdersList() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getOrders()
      .then(setOrders)
      .catch((e) =>
        setError(e instanceof Error ? e.message : "Error al cargar pedidos")
      )
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <div className="flex min-h-60 items-center justify-center">
        <Loader2 className="size-6 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-6 text-center text-destructive">
        {error}
      </div>
    );
  }

  if (orders.length === 0) {
    return (
      <div className="rounded-lg border border-dashed p-12 text-center">
        <p className="text-muted-foreground">Todavía no hiciste pedidos.</p>
        <Button asChild className="mt-4">
          <Link href="/productos">Ver productos</Link>
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-3">
      {orders.map((o) => (
        <Link
          key={o.id}
          href={`/cuenta/ordenes/${o.id}`}
          className="flex items-center gap-4 rounded-lg border bg-card p-4 transition-shadow hover:shadow-md"
        >
          <div className="flex-1">
            <p className="font-mono text-xs text-muted-foreground">
              #{o.id.slice(0, 10)}
            </p>
            <p className="mt-1 text-sm">
              {o.items?.length ?? 0} producto
              {(o.items?.length ?? 0) === 1 ? "" : "s"}
            </p>
            <div className="mt-2 flex flex-wrap gap-2">
              <Badge variant={orderStatusVariant(o.status)}>
                {orderStatusLabel(o.status)}
              </Badge>
              <Badge variant={paymentStatusVariant(o.payment_status)}>
                {paymentStatusLabel(o.payment_status)}
              </Badge>
            </div>
          </div>
          <div className="text-right">
            <p className="font-bold">{formatPrice(o.total_amount)}</p>
          </div>
          <ChevronRight className="size-5 text-muted-foreground" />
        </Link>
      ))}
    </div>
  );
}

export default function OrdersPage() {
  return (
    <div className="mx-auto max-w-3xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-bold">Mis pedidos</h1>
      <RequireAuth>
        <OrdersList />
      </RequireAuth>
    </div>
  );
}
