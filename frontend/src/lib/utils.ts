import { clsx, type ClassValue } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

const priceFormatter = new Intl.NumberFormat("es-AR", {
  style: "currency",
  currency: "ARS",
  minimumFractionDigits: 2,
});

export function formatPrice(value: number | string | null | undefined): string {
  const n = typeof value === "string" ? parseFloat(value) : value ?? 0;
  return priceFormatter.format(Number.isFinite(n as number) ? (n as number) : 0);
}

export function toNum(value: number | string | null | undefined): number {
  const n = typeof value === "string" ? parseFloat(value) : value ?? 0;
  return Number.isFinite(n as number) ? (n as number) : 0;
}

// Resuelve la mejor imagen disponible de un producto (Cloudinary o main_image)
export function productImage(p: {
  images?: { url: string }[];
  main_image?: string | null;
}): string | null {
  return p.images?.[0]?.url ?? p.main_image ?? null;
}
