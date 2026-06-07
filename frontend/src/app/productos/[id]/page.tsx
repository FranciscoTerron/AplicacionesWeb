import { notFound } from "next/navigation";
import Link from "next/link";
import { getProduct } from "@/lib/endpoints";
import { ApiError } from "@/lib/api";
import { ProductGallery } from "@/components/product-gallery";
import { AddToCartButton } from "@/components/add-to-cart-button";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { formatPrice } from "@/lib/utils";
import type { Product } from "@/types/api";

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

  const outOfStock = product.stock <= 0;

  return (
    <div className="mx-auto max-w-5xl px-4 py-6">
      <nav className="mb-4 text-sm text-muted-foreground">
        <Link href="/" className="hover:text-primary">
          Inicio
        </Link>{" "}
        /{" "}
        <Link href="/productos" className="hover:text-primary">
          Productos
        </Link>{" "}
        / <span className="text-foreground">{product.name}</span>
      </nav>

      <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
        <ProductGallery images={product.images} name={product.name} />

        <div className="space-y-4">
          {product.featured && (
            <Badge className="bg-brand-yellow text-brand-yellow-foreground">
              Destacado
            </Badge>
          )}
          <h1 className="text-2xl font-bold">{product.name}</h1>
          <p className="text-sm text-muted-foreground">SKU: {product.sku}</p>

          <p className="text-4xl font-extrabold">
            {formatPrice(product.price)}
          </p>

          <div>
            {outOfStock ? (
              <Badge variant="destructive">Sin stock</Badge>
            ) : (
              <p className="text-sm text-green-600">
                Stock disponible: {product.stock} unidades
              </p>
            )}
          </div>

          <AddToCartButton
            productId={product.id}
            disabled={outOfStock}
            size="lg"
            className="w-full md:w-auto"
            label={outOfStock ? "Sin stock" : "Agregar al carrito"}
          />

          {product.description && (
            <>
              <Separator />
              <div>
                <h2 className="mb-2 font-semibold">Descripción</h2>
                <p className="whitespace-pre-line text-sm text-muted-foreground">
                  {product.description}
                </p>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
