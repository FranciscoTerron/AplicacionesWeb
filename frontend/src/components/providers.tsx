"use client";

import { AuthProvider } from "@/context/auth-context";
import { CartProvider } from "@/context/cart-context";
import { Toaster } from "@/components/ui/sonner";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      <CartProvider>
        {children}
        <Toaster richColors position="top-center" />
      </CartProvider>
    </AuthProvider>
  );
}
