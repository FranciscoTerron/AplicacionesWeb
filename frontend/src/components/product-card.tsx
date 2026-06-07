import Image from "next/image";
import Link from "next/link";
import { Badge } from "@/components/ui/badge";
import { formatPrice } from "@/lib/utils";
import { AddToCartButton } from "./add-to-cart-button";
import type { Product } from "@/types/api";

export function ProductCard({ product }: { product: Product }) {
  const img = product.images?.[0]?.url;
  const outOfStock = product.stock <= 0;

  return (
    <div className="group flex flex-col overflow-hidden rounded-lg border bg-card transition-shadow hover:shadow-md">
      <Link
        href={`/productos/${product.id}`}
        className="relative block aspect-square overflow-hidden bg-muted"
      >
        {img ? (
          <Image
            src={img}
            alt={product.name}
            fill
            sizes="(max-width: 768px) 50vw, 25vw"
            className="object-cover transition-transform group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full items-center justify-center text-muted-foreground">
            Sin imagen
          </div>
        )}
        {product.featured && (
          <Badge className="absolute left-2 top-2 bg-brand-yellow text-brand-yellow-foreground">
            Destacado
          </Badge>
        )}
        {outOfStock && (
          <Badge variant="destructive" className="absolute right-2 top-2">
            Sin stock
          </Badge>
        )}
      </Link>

      <div className="flex flex-1 flex-col gap-2 p-3">
        <Link href={`/productos/${product.id}`} className="flex-1">
          <h3 className="line-clamp-2 text-sm font-medium hover:text-primary">
            {product.name}
          </h3>
        </Link>
        <p className="text-xl font-bold">{formatPrice(product.price)}</p>
        {!outOfStock && product.stock <= (product.min_stock ?? 0) && (
          <p className="text-xs text-destructive">
            ¡Últimas {product.stock} unidades!
          </p>
        )}
        <AddToCartButton
          productId={product.id}
          disabled={outOfStock}
          size="sm"
          className="w-full"
          label={outOfStock ? "Sin stock" : "Agregar"}
        />
      </div>
    </div>
  );
}
