import { notFound } from "next/navigation";
import Link from "next/link";
import {
  Truck,
  ShieldCheck,
  RotateCcw,
  Check,
  ChevronRight,
} from "lucide-react";
import { getProduct } from "@/lib/endpoints";
import { ApiError } from "@/lib/api";
import { ProductGallery } from "@/components/product-gallery";
import { ProductPurchase } from "@/components/product-purchase";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { formatPrice, toNum } from "@/lib/utils";
import type { Product, ProductImage } from "@/types/api";

export const revalidate = 60;

export default async function ProductDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;

  let product: Product;
  try {
    product = await getProduct(id);
  } catch (e) {
    if (e instanceof ApiError && e.status === 404) notFound();
    throw e;
  }

  const stock = toNum(product.stock);
  const minStock = toNum(product.min_stock);
  const outOfStock = stock <= 0;
  const lowStock = !outOfStock && minStock > 0 && stock <= minStock;
  const gallery: ProductImage[] =
    product.images?.length > 0
      ? product.images
      : product.main_image
        ? [{ url: product.main_image, public_id: "main" }]
        : [];

  return (
    <div className="mx-auto max-w-6xl px-4 py-6">
      {/* Breadcrumb */}
      <nav className="mb-5 flex items-center gap-1 text-sm text-muted-foreground">
        <Link href="/" className="hover:text-primary">
          Inicio
        </Link>
        <ChevronRight className="size-3.5" />
        <Link href="/productos" className="hover:text-primary">
          Productos
        </Link>
        <ChevronRight className="size-3.5" />
        <span className="line-clamp-1 text-foreground">{product.name}</span>
      </nav>

      <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
        {/* Galería */}
        <div className="rounded-2xl border bg-card p-4">
          <ProductGallery images={gallery} name={product.name} />
        </div>

        {/* Info + compra */}
        <div className="flex flex-col gap-5">
          <div className="space-y-2">
            <div className="flex flex-wrap items-center gap-2">
              {product.featured && (
                <Badge className="bg-brand-accent text-brand-accent-foreground">
                  ★ Destacado
                </Badge>
              )}
              <span className="text-xs text-muted-foreground">
                SKU: {product.sku}
              </span>
            </div>
            <h1 className="text-2xl font-bold leading-tight md:text-3xl">
              {product.name}
            </h1>
          </div>

          <div className="rounded-2xl border bg-card p-5">
            <p className="text-4xl font-extrabold tracking-tight">
              {formatPrice(product.price)}
            </p>

            <div className="mt-2">
              {outOfStock ? (
                <Badge variant="destructive">Sin stock</Badge>
              ) : (
                <p className="flex items-center gap-1.5 text-sm font-medium text-green-600">
                  <Check className="size-4" />
                  {lowStock
                    ? `¡Últimas ${stock} unidades!`
                    : `Stock disponible (${stock} u.)`}
                </p>
              )}
            </div>

            <div className="mt-5">
              <ProductPurchase
                productId={product.id}
                stock={stock}
                outOfStock={outOfStock}
              />
            </div>
          </div>

          {/* Trust row */}
          <div className="grid grid-cols-1 gap-3 rounded-2xl border bg-sky-50/50 p-4 sm:grid-cols-3">
            <div className="flex items-center gap-2 text-sm">
              <Truck className="size-5 shrink-0 text-primary" />
              <span>Envío a todo el país</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <ShieldCheck className="size-5 shrink-0 text-primary" />
              <span>Compra protegida</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <RotateCcw className="size-5 shrink-0 text-primary" />
              <span>Cambios y devoluciones</span>
            </div>
          </div>

          {product.description && (
            <div className="rounded-2xl border bg-card p-5">
              <h2 className="mb-2 font-semibold">Descripción</h2>
              <Separator className="mb-3" />
              <p className="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                {product.description}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
