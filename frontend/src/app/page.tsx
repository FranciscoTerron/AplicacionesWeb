import Link from "next/link";
import Image from "next/image";
import { getFeatured, getCategories } from "@/lib/endpoints";
import { ProductGrid } from "@/components/product-grid";
import type { Category, Product } from "@/types/api";

export const revalidate = 60;

export default async function HomePage() {
  let featured: Product[] = [];
  let categories: Category[] = [];
  try {
    [featured, categories] = await Promise.all([
      getFeatured(),
      getCategories(),
    ]);
  } catch {
    // backend offline / sin datos: mostrar página vacía sin romper
  }

  return (
    <div className="mx-auto max-w-7xl px-4 py-6">
      {/* Hero */}
      <section className="mb-8 overflow-hidden rounded-xl bg-gradient-to-r from-primary to-blue-500 px-6 py-10 text-primary-foreground md:px-10 md:py-14">
        <h1 className="max-w-xl text-3xl font-extrabold md:text-4xl">
          Todo para tu piscina, en un solo lugar
        </h1>
        <p className="mt-2 max-w-lg text-primary-foreground/90">
          Piscinas, bombas, accesorios y mantenimiento. Envíos a todo el país.
        </p>
        <Link
          href="/productos"
          className="mt-4 inline-block rounded-lg bg-brand-yellow px-5 py-2.5 font-semibold text-brand-yellow-foreground hover:opacity-90"
        >
          Ver productos
        </Link>
      </section>

      {/* Categorías */}
      {categories.length > 0 && (
        <section className="mb-10">
          <h2 className="mb-4 text-xl font-bold">Categorías</h2>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            {categories.map((c) => (
              <Link
                key={c.id}
                href={`/productos?category=${c.id}`}
                className="flex flex-col items-center gap-2 rounded-lg border bg-card p-3 text-center transition-shadow hover:shadow-md"
              >
                <div className="relative size-16 overflow-hidden rounded-full bg-muted">
                  {c.image?.url && (
                    <Image
                      src={c.image.url}
                      alt={c.name}
                      fill
                      sizes="64px"
                      className="object-cover"
                    />
                  )}
                </div>
                <span className="text-sm font-medium">{c.name}</span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Destacados */}
      <section>
        <h2 className="mb-4 text-xl font-bold">Productos destacados</h2>
        {featured.length > 0 ? (
          <ProductGrid products={featured} />
        ) : (
          <p className="rounded-lg border border-dashed p-8 text-center text-muted-foreground">
            No hay productos destacados por ahora.{" "}
            <Link href="/productos" className="text-primary underline">
              Ver todo el catálogo
            </Link>
          </p>
        )}
      </section>
    </div>
  );
}
