import { apiFetch, apiFetchRaw } from "./api";
import type {
  AuthResponse,
  Cart,
  Category,
  CreateOrderBody,
  Discount,
  Order,
  Product,
  RegisterBody,
} from "@/types/api";

// ---------- Catálogo (público) ----------

export interface ProductQuery {
  search?: string;
  category?: string;
  featured?: boolean;
  min_price?: number;
  max_price?: number;
  page?: number;
  limit?: number;
}

function toQuery(params: Record<string, unknown>): string {
  const sp = new URLSearchParams();
  for (const [k, v] of Object.entries(params)) {
    if (v === undefined || v === null || v === "") continue;
    sp.set(k, String(v));
  }
  const s = sp.toString();
  return s ? `?${s}` : "";
}

export function getProducts(params: ProductQuery = {}) {
  return apiFetch<Product[]>(
    `/catalog/products${toQuery(params as Record<string, unknown>)}`,
    {
      next: { revalidate: 60 },
    }
  );
}

export async function getFeatured() {
  const { data } = await apiFetch<Product[]>("/catalog/featured", {
    next: { revalidate: 60 },
  });
  return data;
}

export async function getProduct(id: string) {
  const { data } = await apiFetch<Product>(`/catalog/products/${id}`, {
    next: { revalidate: 60 },
  });
  return data;
}

export async function getCategories() {
  const { data } = await apiFetch<Category[]>("/catalog/categories", {
    next: { revalidate: 300 },
  });
  return data;
}

export interface SearchBody {
  query?: string;
  category_id?: string;
  min_price?: number;
  max_price?: number;
  in_stock?: boolean;
  sort_by?: "price" | "name" | "rating" | "created_at";
  sort_order?: "asc" | "desc";
}

export async function searchProducts(body: SearchBody) {
  const { data } = await apiFetch<Product[]>("/catalog/search", {
    method: "POST",
    body,
  });
  return data;
}

// ---------- Auth ----------

export function login(email: string, password: string) {
  return apiFetchRaw<AuthResponse & { success: boolean }>("/auth/login", {
    method: "POST",
    body: { email, password },
  });
}

export function register(body: RegisterBody) {
  return apiFetchRaw<AuthResponse & { success: boolean }>("/auth/register", {
    method: "POST",
    body,
  });
}

export function logout() {
  return apiFetch<unknown>("/auth/logout", { method: "POST", auth: true });
}

// ---------- Carrito (protegido) ----------

export async function getCart() {
  const { data } = await apiFetch<Cart>("/cart", { auth: true });
  return data;
}

export async function mutateCart(
  action: "add" | "update" | "remove",
  product_id: string,
  quantity = 1
) {
  const { data } = await apiFetch<Cart>("/cart", {
    method: "POST",
    auth: true,
    body: { action, product_id, quantity },
  });
  return data;
}

// ---------- Descuentos (protegido) ----------

export async function validateDiscount(
  code: string,
  opts: { product_id?: string; category_id?: string } = {}
) {
  const { data } = await apiFetch<Discount>("/discounts/validate", {
    method: "POST",
    auth: true,
    body: { code, ...opts },
  });
  return data;
}

// ---------- Órdenes (protegido) ----------

export async function createOrder(body: CreateOrderBody) {
  const { data } = await apiFetch<Order>("/orders", {
    method: "POST",
    auth: true,
    body,
  });
  return data;
}

export async function getOrders(params: { status?: string } = {}) {
  const { data } = await apiFetch<Order[]>(
    `/orders${toQuery(params as Record<string, unknown>)}`, {
    auth: true,
    cache: "no-store",
  });
  return data;
}

export async function getOrder(id: string) {
  const { data } = await apiFetch<Order>(`/orders/${id}`, {
    auth: true,
    cache: "no-store",
  });
  return data;
}

export async function cancelOrder(id: string) {
  const { data } = await apiFetch<Order>(`/orders/${id}/cancel`, {
    method: "PUT",
    auth: true,
  });
  return data;
}
