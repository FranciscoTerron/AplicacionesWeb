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
      <section className="relative mb-10 overflow-hidden rounded-2xl bg-gradient-to-br from-sky-600 via-sky-500 to-cyan-400 px-6 py-12 text-white shadow-lg md:px-12 md:py-16">
        {/* burbujas decorativas */}
        <div className="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-white/10 blur-2xl" />
        <div className="pointer-events-none absolute -bottom-20 right-32 size-48 rounded-full bg-cyan-200/20 blur-2xl" />
        <div className="relative max-w-xl">
          <span className="mb-3 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-sm">
            Envíos a todo el país 🏊
          </span>
          <h1 className="text-3xl font-extrabold leading-tight tracking-tight md:text-5xl">
            Todo para tu piscina, en un solo lugar
          </h1>
          <p className="mt-3 max-w-lg text-white/90">
            Piscinas, bombas, accesorios y productos de mantenimiento al mejor
            precio.
          </p>
          <Link
            href="/productos"
            className="mt-5 inline-block rounded-lg bg-white px-6 py-3 font-semibold text-primary shadow-md transition-transform hover:scale-105 hover:bg-white/95"
          >
            Ver productos
          </Link>
        </div>
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
                className="group flex flex-col items-center gap-2 rounded-xl border bg-card p-4 text-center transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
              >
                <div className="relative size-16 overflow-hidden rounded-full bg-gradient-to-br from-sky-100 to-cyan-100 ring-1 ring-border">
                  {c.image?.url ? (
                    <Image
                      src={c.image.url}
                      alt={c.name}
                      fill
                      sizes="64px"
                      className="object-cover transition-transform group-hover:scale-110"
                    />
                  ) : (
                    <span className="flex h-full items-center justify-center text-xl font-bold text-primary/40">
                      {c.name.charAt(0)}
                    </span>
                  )}
                </div>
                <span className="text-sm font-medium group-hover:text-primary">
                  {c.name}
                </span>
              </Link>
            ))}
          </div>
        </section>
      )}

      {/* Destacados */}
      <section>
        <div className="mb-4 flex items-end justify-between">
          <h2 className="text-xl font-bold">Productos destacados</h2>
          <Link
            href="/productos"
            className="text-sm font-medium text-primary hover:underline"
          >
            Ver todos →
          </Link>
        </div>
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
