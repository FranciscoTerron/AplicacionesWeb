import { ProductCard } from "./product-card";
import { Skeleton } from "@/components/ui/skeleton";
import type { Product } from "@/types/api";

export function ProductGrid({ products }: { products: Product[] }) {
  if (products.length === 0) {
    return (
      <div className="flex min-h-60 items-center justify-center rounded-lg border border-dashed text-muted-foreground">
        No se encontraron productos.
      </div>
    );
  }
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      {products.map((p) => (
        <ProductCard key={p.id} product={p} />
      ))}
    </div>
  );
}

export function ProductGridSkeleton({ count = 8 }: { count?: number }) {
  return (
    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      {Array.from({ length: count }).map((_, i) => (
        <div key={i} className="overflow-hidden rounded-lg border">
          <Skeleton className="aspect-square w-full" />
          <div className="space-y-2 p-3">
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-6 w-24" />
            <Skeleton className="h-8 w-full" />
          </div>
        </div>
      ))}
    </div>
  );
}
