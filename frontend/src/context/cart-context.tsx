"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
import * as api from "@/lib/endpoints";
import type { CartItem } from "@/types/api";
import { useAuth } from "./auth-context";

interface CartContextValue {
  items: CartItem[];
  count: number;
  loading: boolean;
  refresh: () => Promise<void>;
  add: (productId: string, quantity?: number) => Promise<void>;
  update: (productId: string, quantity: number) => Promise<void>;
  remove: (productId: string) => Promise<void>;
  clear: () => Promise<void>;
  clearLocal: () => void;
}

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const { isAuthenticated } = useAuth();
  const [items, setItems] = useState<CartItem[]>([]);
  const [loading, setLoading] = useState(false);

  const refresh = useCallback(async () => {
    if (!isAuthenticated) {
      setItems([]);
      return;
    }
    setLoading(true);
    try {
      const cart = await api.getCart();
      setItems(cart.items ?? []);
    } catch {
      setItems([]);
    } finally {
      setLoading(false);
    }
  }, [isAuthenticated]);

  useEffect(() => {
    refresh();
  }, [refresh]);

  const add = useCallback(async (productId: string, quantity = 1) => {
    const cart = await api.mutateCart("add", productId, quantity);
    setItems(cart.items ?? []);
  }, []);

  const update = useCallback(async (productId: string, quantity: number) => {
    const cart = await api.mutateCart("update", productId, quantity);
    setItems(cart.items ?? []);
  }, []);

  const remove = useCallback(async (productId: string) => {
    const cart = await api.mutateCart("remove", productId);
    setItems(cart.items ?? []);
  }, []);

  const clear = useCallback(async () => {
    const cart = await api.clearCart();
    setItems(cart.items ?? []);
  }, []);

  // Vacía solo el estado local. Se usa tras crear la orden: el backend ya
  // limpió el carrito server-side, así que acá solo sincronizamos la UI/badge.
  const clearLocal = useCallback(() => setItems([]), []);

  const count = items.reduce((acc, it) => acc + (it.quantity ?? 0), 0);

  return (
    <CartContext.Provider
      value={{ items, count, loading, refresh, add, update, remove, clear, clearLocal }}
    >
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart debe usarse dentro de <CartProvider>");
  return ctx;
}
