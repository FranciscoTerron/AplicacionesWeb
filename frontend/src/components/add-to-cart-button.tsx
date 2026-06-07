"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { ShoppingCart, Loader2 } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { useAuth } from "@/context/auth-context";
import { useCart } from "@/context/cart-context";

interface Props {
  productId: string;
  disabled?: boolean;
  quantity?: number;
  className?: string;
  size?: "default" | "sm" | "lg";
  label?: string;
}

export function AddToCartButton({
  productId,
  disabled,
  quantity = 1,
  className,
  size = "default",
  label = "Agregar al carrito",
}: Props) {
  const { isAuthenticated } = useAuth();
  const { add } = useCart();
  const router = useRouter();
  const [loading, setLoading] = useState(false);

  async function handleClick() {
    if (!isAuthenticated) {
      toast.info("Iniciá sesión para agregar productos al carrito");
      router.push("/login?redirect=" + encodeURIComponent(location.pathname));
      return;
    }
    setLoading(true);
    try {
      await add(productId, quantity);
      toast.success("Producto agregado al carrito");
    } catch (e) {
      toast.error(e instanceof Error ? e.message : "No se pudo agregar");
    } finally {
      setLoading(false);
    }
  }

  return (
    <Button
      onClick={handleClick}
      disabled={disabled || loading}
      size={size}
      className={className}
    >
      {loading ? (
        <Loader2 className="animate-spin" />
      ) : (
        <ShoppingCart />
      )}
      {label}
    </Button>
  );
}
