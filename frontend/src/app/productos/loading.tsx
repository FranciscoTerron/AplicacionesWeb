import { Skeleton } from "@/components/ui/skeleton";
import { ProductGridSkeleton } from "@/components/product-grid";

export default function ProductsLoading() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <div className="mb-4 flex items-baseline justify-between border-b pb-3">
        <Skeleton className="h-7 w-40" />
        <Skeleton className="h-4 w-32" />
      </div>
      <div className="grid grid-cols-1 gap-6 md:grid-cols-[220px_1fr]">
        <div className="hidden space-y-3 md:block">
          <Skeleton className="h-5 w-24" />
          {Array.from({ length: 6 }).map((_, i) => (
            <Skeleton key={i} className="h-4 w-32" />
          ))}
        </div>
        <ProductGridSkeleton count={8} />
      </div>
    </div>
  );
}
