import type { Discount } from "@/types/api";

// Regla de descuentos del checkout, unificada en un solo lugar para que el
// total mostrado en carrito y checkout coincida SIEMPRE con el que calcula el
// backend al crear la orden (DiscountService::applyValue + OrderApiController@store).
// El cupón y el descuento automático por producto NO se apilan: gana el que más
// baja el total.

/** Monto que descuenta un cupón sobre un subtotal base. 0 si no hay cupón. */
export function couponAmount(
  discount: Discount | null,
  baseSubtotal: number
): number {
  if (!discount) return 0;

  return discount.discount_type === "percentage"
    ? (baseSubtotal * discount.value) / 100
    : Math.min(discount.value, baseSubtotal);
}

/** Mejor descuento entre el cupón y el descuento automático (no se apilan). */
export function bestDiscount(
  autoDiscount: number,
  discount: Discount | null,
  baseSubtotal: number
): number {
  return Math.max(autoDiscount, couponAmount(discount, baseSubtotal));
}
