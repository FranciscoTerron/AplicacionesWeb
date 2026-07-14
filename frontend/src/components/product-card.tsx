import Image from "next/image";
import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { formatPrice, productImage, toNum } from "@/lib/utils";
import { AddToCartButton } from "./add-to-cart-button";
import type { Product } from "@/types/api";

export function ProductCard({ product }: { product: Product }) {
  const img = productImage(product);
  const stock = toNum(product.stock);
  const minStock = toNum(product.min_stock);
  const outOfStock = stock <= 0;
  const lowStock = !outOfStock && minStock > 0 && stock <= minStock;
  const hasDiscount = product.has_discount && product.discount;

  return (
    <div className="group relative flex flex-col overflow-hidden rounded-xl border bg-card transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg hover:shadow-primary/5">
      <Link
        href={`/productos/${product.id}`}
        className="relative block aspect-square overflow-hidden bg-gradient-to-br from-sky-50 to-cyan-50"
      >
        {img ? (
          <Image
            src={img}
            alt={product.name}
            fill
            sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
            className="object-cover transition-transform duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-sm text-muted-foreground">
            Sin imagen
          </div>
        )}
        <div className="absolute left-2 top-2 flex flex-col gap-1">
          {product.featured && (
            <Badge className="bg-brand-accent text-brand-accent-foreground shadow-sm">
              ★ Destacado
            </Badge>
          )}
          {hasDiscount && (
            <Badge className="bg-red-600 text-white shadow-sm">
              -{product.discount?.percent_off}%
            </Badge>
          )}
        </div>
        {outOfStock && (
          <div className="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-[1px]">
            <Badge variant="destructive">Sin stock</Badge>
          </div>
        )}
      </Link>

      <div className="flex flex-1 flex-col gap-1.5 p-3">
        <Link href={`/productos/${product.id}`} className="flex-1">
          <h3 className="line-clamp-2 text-sm font-medium leading-snug text-foreground/90 transition-colors group-hover:text-primary">
            {product.name}
          </h3>
        </Link>
        {hasDiscount ? (
          <div className="flex items-baseline gap-1.5">
            <p className="text-lg font-bold tracking-tight text-red-600">
              {formatPrice(product.final_price)}
            </p>
            <p className="text-sm text-muted-foreground line-through">
              {formatPrice(product.price)}
            </p>
          </div>
        ) : (
          <p className="text-lg font-bold tracking-tight">
            {formatPrice(product.price)}
          </p>
        )}
        {lowStock && (
          <p className="text-xs font-medium text-orange-600">
            ¡Quedan {stock}!
          </p>
        )}
        <AddToCartButton
          productId={product.id}
          disabled={outOfStock}
          size="sm"
          className="mt-1 w-full"
          label={outOfStock ? "Sin stock" : "Agregar"}
        />
      </div>
    </div>
  );
}
