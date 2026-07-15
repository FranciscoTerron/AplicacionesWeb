import {
  getProducts,
  getCategories,
  searchProducts,
  type SearchBody,
} from "@/lib/endpoints";
import { ProductGrid } from "@/components/product-grid";
import { FiltersSidebar } from "@/components/filters-sidebar";
import { MobileFilters } from "@/components/mobile-filters";
import { SortSelect } from "@/components/sort-select";
import { Pagination } from "@/components/pagination";
import type { Category, Product, PaginationMeta } from "@/types/api";

export const dynamic = "force-dynamic";

interface SearchParams {
  search?: string;
  category?: string;
  min_price?: string;
  max_price?: string;
  sort?: string;
  page?: string;
}

const SORT_MAP: Record<string, Pick<SearchBody, "sort_by" | "sort_order">> = {
  price_asc: { sort_by: "price", sort_order: "asc" },
  price_desc: { sort_by: "price", sort_order: "desc" },
  name: { sort_by: "name", sort_order: "asc" },
};

export default async function ProductsPage({
  searchParams,
}: {
  searchParams: Promise<SearchParams>;
}) {
  const sp = await searchParams;
  const page = Number(sp.page) || 1;
  const sortCfg = sp.sort ? SORT_MAP[sp.sort] : undefined;

  let products: Product[] = [];
  let meta: PaginationMeta | undefined;
  let total = 0;
  let categories: Category[] = [];
  let error: string | null = null;

  const minPrice = sp.min_price ? Number(sp.min_price) : undefined;
  const maxPrice = sp.max_price ? Number(sp.max_price) : undefined;

  try {
    if (sortCfg) {
      // Ordenado: el endpoint de búsqueda soporta sort (devuelve todo, sin paginar)
      const [list, cats] = await Promise.all([
        searchProducts({
          query: sp.search,
          category_id: sp.category,
          min_price: minPrice,
          max_price: maxPrice,
          ...sortCfg,
        }),
        getCategories(),
      ]);
      products = list;
      total = list.length;
      categories = cats;
    } else {
      const [res, cats] = await Promise.all([
        getProducts({
          search: sp.search,
          category: sp.category,
          min_price: minPrice,
          max_price: maxPrice,
          page,
          limit: 20,
        }),
        getCategories(),
      ]);
      products = res.data;
      meta = res.meta;
      total = res.meta?.total ?? res.data.length;
      categories = cats;
    }
  } catch (e) {
    error = e instanceof Error ? e.message : "Error al cargar productos";
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b pb-3">
        <h1 className="text-2xl font-bold">
          {sp.search ? `Resultados para "${sp.search}"` : "Productos"}
        </h1>
        <div className="flex items-center gap-2">
          {!error && (
            <span className="hidden text-sm text-muted-foreground sm:inline">
              {total} producto{total === 1 ? "" : "s"}
            </span>
          )}
          <MobileFilters categories={categories} className="md:hidden" />
          <SortSelect />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 md:grid-cols-[220px_1fr]">
        <div className="hidden md:block">
          <FiltersSidebar categories={categories} />
        </div>

        <div>
          {/* Heading intermedio (h1 → h3 de las cards saltaba un nivel, HU-F15). */}
          <h2 className="sr-only">Resultados</h2>
          {error ? (
            <div className="rounded-lg border border-destructive/40 bg-destructive/5 p-6 text-center text-destructive">
              {error}
            </div>
          ) : (
            <>
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
