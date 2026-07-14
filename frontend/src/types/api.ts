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

// Descuento automático aplicado a un producto (vía Product.discount_id).
export interface ProductDiscount {
  id: string | null;
  code: string | null;
  name: string | null;
  discount_type: "percentage" | "fixed" | string;
  value: number;
  percent_off: number;
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
  discount_id?: string | null;
  // El backend decora todo producto de catálogo con el precio final ya
  // calculado: has_discount/final_price/discount_amount/discount.
  has_discount?: boolean;
  final_price?: number | string;
  discount_amount?: number | string;
  discount?: ProductDiscount | null;
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
  // Comparación cupón vs. descuento automático del producto, solo cuando
  // /discounts/validate se llama con product_id.
  pricing?: {
    base: number;
    coupon_final: number;
    auto_final: number;
    final: number;
    amount: number;
    applied: "coupon" | "auto";
  } | null;
}

// Descuento automático ya aplicado a un ítem de orden (gana sobre el cupón
// si baja más el precio; ver OrderApiController@store).
export interface OrderItemDiscount {
  id: string | null;
  code: string | null;
  name: string | null;
  amount: number;
}

export interface OrderItem {
  product_id: string;
  name: string;
  quantity: number;
  // Precio final por unidad, ya con el descuento (si aplica) aplicado.
  price: number;
  base_price?: number;
  discount?: OrderItemDiscount | null;
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
  // Suma de precios base (sin descuentos). total_amount es lo realmente cobrado.
  subtotal?: number;
  discount_total?: number;
  coupon?: { code: string; name: string | null; amount: number } | null;
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
  discount_code?: string;
}

export interface RegisterBody {
  name: string;
  email: string;
  password: string;
  phone?: string;
  address?: string;
}
