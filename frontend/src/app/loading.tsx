import { Skeleton } from "@/components/ui/skeleton";
import { ProductGridSkeleton } from "@/components/product-grid";

export default function HomeLoading() {
  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <Skeleton className="mb-10 h-56 w-full rounded-2xl" />
      <Skeleton className="mb-4 h-6 w-40" />
      <div className="mb-10 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
        {Array.from({ length: 6 }).map((_, i) => (
          <Skeleton key={i} className="h-28 rounded-xl" />
        ))}
      </div>
      <Skeleton className="mb-4 h-6 w-48" />
      <ProductGridSkeleton count={8} />
    </div>
  );
}
