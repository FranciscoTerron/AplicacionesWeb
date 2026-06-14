// Tipos basados en los shapes reales de la API Laravel /api/v1

export interface ApiResponse<T> {
  success: boolean;
  message?: string;
  data: T;
  meta?: PaginationMeta;
}

export interface PaginationMeta {
  total: number;
  page: number;
  last_page: number;
  per_page: number;
}

export interface ProductImage {
  url: string;
  public_id: string;
}

export interface Product {
  id: string;
  name: string;
  description: string | null;
  category_id: string;
  subcategory_id: string | null;
  sku: string;
  price: number | string;
  cost?: number | string | null;
  stock: number;
  min_stock?: number;
  images: ProductImage[];
  main_image?: string | null;
  active: boolean;
  featured: boolean;
  dimensions?: Record<string, unknown> | null;
  created_at?: string;
  updated_at?: string;
}

export interface Category {
  id: string;
  name: string;
  description: string | null;
  active: boolean;
  order?: number;
  image?: ProductImage | null;
}

export interface User {
  id: string;
  name: string;
  email: string;
  role: string;
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface CartItem {
  product_id: string;
  quantity: number;
}

export interface Cart {
  id: string;
  user_id: string;
  items: CartItem[];
  created_at?: string;
  updated_at?: string;
}

// Item de carrito enriquecido con datos del producto (client-side)
export interface EnrichedCartItem extends CartItem {
  product: Product | null;
}

export interface Discount {
  id: string;
  code: string;
  name: string;
  description: string | null;
  discount_type: "percentage" | "fixed" | string;
  value: number;
  applies_to: "all" | "product" | "category" | string;
}

export interface OrderItem {
  product_id: string;
  name: string;
  quantity: number;
  price: number;
}

export type OrderStatus =
  | "pending"
  | "confirmed"
  | "processing"
  | "shipped"
  | "delivered"
  | "cancelled";

export type PaymentStatus =
  | "pending"
  | "approved"
  | "rejected"
  | "refunded"
  | "completed"
  | "failed";

export type PaymentMethod = "cash" | "card" | "transfer" | "mercado_pago";

export interface Order {
  id: string;
  user_id: string;
  items: OrderItem[];
  shipping_address: string;
  payment_method: PaymentMethod | string;
  total_amount: number;
  status: OrderStatus | string;
  payment_status: PaymentStatus | string;
  created_at?: string;
  updated_at?: string;
}

export interface CreateOrderBody {
  items: CartItem[];
  shipping_address: string;
  payment_method: PaymentMethod;
}

export interface RegisterBody {
  name: string;
  email: string;
  password: string;
  phone?: string;
  address?: string;
}
