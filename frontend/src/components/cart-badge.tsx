"use client";

import Link from "next/link";
import { ShoppingCart } from "lucide-react";
import { useCart } from "@/context/cart-context";

export function CartBadge() {
  const { count } = useCart();
  return (
    <Link
      href="/carrito"
      className="relative flex items-center gap-2 rounded-md px-2 py-1.5 text-sm font-medium hover:bg-black/5"
      aria-label="Carrito"
    >
      <span className="relative">
        <ShoppingCart className="size-5" />
        {count > 0 && (
          <span className="absolute -right-2 -top-2 flex size-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">
            {count > 9 ? "9+" : count}
          </span>
        )}
      </span>
      <span className="hidden sm:inline">Carrito</span>
    </Link>
  );
}
