import type { OrderStatus, PaymentStatus } from "@/types/api";

// Labels alineados con el backend (OrderController::statuses()).
export const ORDER_STATUS_LABELS: Record<string, string> = {
  pending: "Pendiente",
  confirmed: "Confirmada",
  in_process: "En proceso",
  completed: "Completada",
  cancelled: "Cancelada",
};

export const PAYMENT_STATUS_LABELS: Record<string, string> = {
  pending: "Pago pendiente",
  approved: "Pago aprobado",
  completed: "Pago completado",
  rejected: "Pago rechazado",
  refunded: "Reembolsado",
  failed: "Pago fallido",
};

// Variante de Badge de shadcn por estado
export function orderStatusVariant(
  status: string
): "default" | "secondary" | "destructive" | "outline" {
  if (status === "cancelled") return "destructive";
  if (status === "completed") return "default";
  if (status === "pending") return "secondary";
  return "outline";
}

export function paymentStatusVariant(
  status: string
): "default" | "secondary" | "destructive" | "outline" {
  if (status === "approved" || status === "completed") return "default";
  if (status === "rejected" || status === "failed") return "destructive";
  return "secondary";
}

export function orderStatusLabel(status: OrderStatus | string): string {
  return ORDER_STATUS_LABELS[status] ?? status;
}

export function paymentStatusLabel(status: PaymentStatus | string): string {
  return PAYMENT_STATUS_LABELS[status] ?? status;
}

export const CANCELABLE_STATUSES = ["pending", "confirmed"];
