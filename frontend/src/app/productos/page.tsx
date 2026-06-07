import { getProducts, getCategories } from "@/lib/endpoints";
import { ProductGrid } from "@/components/product-grid";
import { FiltersSidebar } from "@/components/filters-sidebar";
import { Pagination } from "@/components/pagination";
import type { Category, Product, PaginationMeta } from "@/types/api";

export const dynamic = "force-dynamic";

interface SearchParams {
  search?: string;
  category?: string;
  min_price?: string;
  max_price?: string;
  page?: string;
}

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const sp = await searchParams;
  const page = Number(sp.page) || 1;

  let products: Product[] = [];
  let meta: PaginationMeta | undefined;
  let categories: Category[] = [];
  let error: string | null = null;

  try {
    const [res, cats] = await Promise.all([
      getProducts({
        search: sp.search,
        category: sp.category,
        min_price: sp.min_price ? Number(sp.min_price) : undefined,
        max_price: sp.max_price ? Number(sp.max_price) : undefined,
        page,
        limit: 20,
      }),
      getCategories(),
    ]);
    products = res.data;
    meta = res.meta;
    categories = cats;
  } catch (e) {
    error = e instanceof Error ? e.message : "Error al cargar productos";
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-bold">
        {sp.search ? `Resultados para "${sp.search}"` : "Productos"}
      </h1>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-[220px_1fr]">
        <FiltersSidebar categories={categories} />

        <div>
          {error ? (
            <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-6 text-center text-destructive">
              {error}
            </div>
          ) : (
            <>
              {meta && (
                <p className="mb-3 text-sm text-muted-foreground">
                  {meta.total} producto{meta.total === 1 ? "" : "s"} encontrado
                  {meta.total === 1 ? "" : "s"}
                </p>
              )}
              <ProductGrid products={products} />
              {meta && (
                <Pagination page={meta.page} lastPage={meta.last_page} />
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
